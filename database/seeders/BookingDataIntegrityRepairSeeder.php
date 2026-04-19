<?php

namespace Database\Seeders;

use App\Models\BookingModel;
use App\Models\BookingPackageModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\SystemRevenueModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingDataIntegrityRepairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->backfillSeededAprilBookings();
        $this->normalizeBookingPackageSnapshots();
        $this->repairOverpaidBookings();

        $this->command?->info('Repaired booking payment and package snapshot integrity.');
    }

    /**
     * Backfill missing booking package snapshots and payment rows for seeded April bookings.
     */
    private function backfillSeededAprilBookings(): void
    {
        $bookings = BookingModel::query()
            ->where('booking_reference', 'like', 'SEED-APR-%')
            ->with(['assignedPhotographers'])
            ->orderBy('id')
            ->get();

        foreach ($bookings as $booking) {
            $assignedPhotographer = $booking->assignedPhotographers->first();

            if (!$assignedPhotographer) {
                continue;
            }

            $package = $this->resolveStudioPackageForBooking(
                (int) $booking->provider_id,
                (int) $booking->category_id,
                (int) $assignedPhotographer->photographer_id,
                (float) $booking->total_amount
            );

            if ($package) {
                BookingPackageModel::updateOrCreate(
                    [
                        'booking_id' => $booking->id,
                        'package_id' => $package->id,
                    ],
                    [
                        'package_type' => 'studio',
                        'package_name' => $package->package_name,
                        'package_price' => $booking->total_amount,
                        'package_inclusions' => $package->package_inclusions,
                        'duration' => $package->duration,
                        'maximum_edited_photos' => $package->maximum_edited_photos,
                        'coverage_scope' => $this->normalizeCoverageScope($package->coverage_scope),
                        'created_at' => $booking->created_at,
                        'updated_at' => $booking->updated_at,
                    ]
                );
            }

            $paidAt = $booking->created_at
                ? $booking->created_at->copy()->addMinutes(15)->toDateTimeString()
                : Carbon::now()->toDateTimeString();

            PaymentModel::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'payment_reference' => 'SEED-PAY-' . $booking->booking_reference,
                ],
                [
                    'stripe_payment_intent_id' => null,
                    'stripe_session_id' => null,
                    'amount' => $booking->total_amount,
                    'payment_method' => 'manual',
                    'status' => 'succeeded',
                    'payment_details' => [
                        'type' => 'booking_integrity_repair',
                        'notes' => 'Backfilled full payment row for seeded April booking.',
                        'is_balance_payment' => false,
                    ],
                    'paid_at' => $paidAt,
                    'created_at' => $booking->created_at,
                    'updated_at' => $booking->updated_at,
                ]
            );

            $booking->payment_type = 'full_payment';
            $booking->down_payment = $booking->total_amount;
            $booking->remaining_balance = 0;
            $booking->deposit_policy = 'full_payment';
            $booking->updatePaymentStatus();
            $booking->status = BookingModel::STATUS_COMPLETED;
            $booking->saveQuietly();
        }
    }

    /**
     * Normalize any booking snapshot inclusions that were stored as JSON strings.
     */
    private function normalizeBookingPackageSnapshots(): void
    {
        $snapshots = BookingPackageModel::query()->get();

        foreach ($snapshots as $snapshot) {
            $normalizedInclusions = $this->decodeJsonArray($snapshot->getRawOriginal('package_inclusions'));

            if ($normalizedInclusions === null) {
                continue;
            }

            DB::table('tbl_booking_packages')
                ->where('id', $snapshot->id)
                ->update([
                    'package_inclusions' => json_encode($normalizedInclusions, JSON_THROW_ON_ERROR),
                ]);
        }
    }

    /**
     * Remove stale succeeded payments that predate the booking and cause overpayment.
     */
    private function repairOverpaidBookings(): void
    {
        $bookings = BookingModel::query()
            ->with('payments')
            ->get();

        foreach ($bookings as $booking) {
            $succeededPayments = $booking->payments->where('status', 'succeeded')->sortBy('created_at')->values();
            $succeededTotal = (float) $succeededPayments->sum('amount');

            if ($succeededTotal <= (float) $booking->total_amount) {
                continue;
            }

            $stalePayments = $succeededPayments->filter(function (PaymentModel $payment) use ($booking) {
                if (!$booking->created_at || !$payment->created_at) {
                    return false;
                }

                return $payment->created_at->lt($booking->created_at->copy()->subMinutes(5));
            })->values();

            if ($stalePayments->isEmpty()) {
                continue;
            }

            foreach ($stalePayments as $payment) {
                SystemRevenueModel::query()->where('payment_id', $payment->id)->delete();
                $payment->delete();
            }

            $booking->refresh();
            $booking->updatePaymentStatus();
            if ($booking->payment_status !== BookingModel::PAYMENT_PAID && $booking->status === BookingModel::STATUS_COMPLETED) {
                $booking->status = BookingModel::STATUS_CONFIRMED;
            }
            $booking->saveQuietly();
        }
    }

    /**
     * Pick the most appropriate studio package for a booking snapshot.
     */
    private function resolveStudioPackageForBooking(
        int $studioId,
        int $bookingCategoryId,
        int $photographerId,
        float $totalAmount
    ): ?PackagesModel {
        $serviceCategoryId = DB::table('tbl_studio_photographers as photographers')
            ->join('tbl_services as services', 'services.id', '=', 'photographers.specialization')
            ->where('photographers.studio_id', $studioId)
            ->where('photographers.photographer_id', $photographerId)
            ->value('services.category_id');

        $candidateCategoryIds = collect([$serviceCategoryId, $bookingCategoryId])
            ->filter()
            ->unique()
            ->values();

        foreach ($candidateCategoryIds as $categoryId) {
            $package = PackagesModel::query()
                ->where('studio_id', $studioId)
                ->where('category_id', $categoryId)
                ->where('status', 'active')
                ->orderByRaw('ABS(package_price - ?) ASC', [$totalAmount])
                ->orderBy('id')
                ->first();

            if ($package) {
                return $package;
            }
        }

        return PackagesModel::query()
            ->where('studio_id', $studioId)
            ->where('status', 'active')
            ->orderByRaw('ABS(package_price - ?) ASC', [$totalAmount])
            ->orderBy('id')
            ->first();
    }

    /**
     * Decode a JSON array or a double-encoded JSON array string.
     *
     * @return array<int, mixed>|null
     */
    private function decodeJsonArray(mixed $value): ?array
    {
        $decoded = $value;

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);

            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
        }

        return is_array($decoded) ? array_values($decoded) : null;
    }

    /**
     * Normalize coverage scope values for booking snapshots.
     */
    private function normalizeCoverageScope(mixed $coverageScope): ?string
    {
        if (is_array($coverageScope)) {
            return json_encode($coverageScope, JSON_THROW_ON_ERROR);
        }

        if (is_string($coverageScope) && trim($coverageScope) !== '') {
            return $coverageScope;
        }

        return null;
    }
}
