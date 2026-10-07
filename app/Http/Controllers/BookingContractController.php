<?php

namespace App\Http\Controllers;

use App\Models\BookingContractModel;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use App\Services\ContractService;
use App\Traits\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookingContractController extends Controller
{
    use Notifiable;

    private const LAYOUTS = [
        'client' => 'layouts.client.app',
        'owner' => 'layouts.owner.app',
        'freelancer' => 'layouts.freelancer.app',
    ];

    /** Largest accepted signature image, as base64 characters (about 150 KB decoded). */
    private const MAX_SIGNATURE_LENGTH = 200000;

    public function __construct(private ContractService $contracts) {}

    /**
     * Contract page: read, sign and print.
     */
    public function show(Request $request, $bookingId)
    {
        [$booking, $party, $role] = $this->resolve($bookingId);
        $contract = $this->contractOf($booking);

        return view('contracts.show', [
            'layout' => self::LAYOUTS[$role],
            'booking' => $booking,
            'contract' => $contract,
            'party' => $party,
            'role' => $role,
            'providerName' => $this->contracts->providerName($booking),
            'signedAt' => $party === 'client' ? $contract->client_signed_at : $contract->studio_signed_at,
        ]);
    }

    /**
     * Store the caller's signature (drawn image plus typed full name).
     */
    public function sign(Request $request, $bookingId)
    {
        [$booking, $party, $role] = $this->resolve($bookingId);
        $contract = $this->contractOf($booking);

        $data = $request->validate([
            'signed_name' => 'required|string|min:3|max:255',
            'signature' => ['required', 'string', 'max:'.self::MAX_SIGNATURE_LENGTH, 'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/'],
        ], [
            'signature.regex' => 'Please draw your signature in the box.',
            'signature.required' => 'Please draw your signature in the box.',
        ]);

        if (in_array($booking->status, [BookingModel::STATUS_CANCELLED, BookingModel::STATUS_COMPLETED], true)) {
            return response()->json(['success' => false, 'message' => 'This booking is closed and can no longer be signed.'], 422);
        }

        $already = $party === 'client' ? $contract->client_signed_at : $contract->studio_signed_at;
        if ($already) {
            return response()->json(['success' => false, 'message' => 'You have already signed this contract.'], 422);
        }

        $image = base64_decode(substr($data['signature'], strlen('data:image/png;base64,')), true);
        if ($image === false || ! str_starts_with($image, "\x89PNG")) {
            return response()->json(['success' => false, 'message' => 'The signature image is not valid.'], 422);
        }

        $path = "contracts/{$booking->id}/{$party}.png";
        Storage::disk('public')->put($path, $image);

        $fullySigned = false;

        DB::transaction(function () use ($contract, $party, $path, $data, &$fullySigned) {
            $contract = BookingContractModel::lockForUpdate()->findOrFail($contract->id);

            if ($party === 'client') {
                $contract->client_signature = $path;
                $contract->client_signed_by = $data['signed_name'];
                $contract->client_signed_at = now();
            } else {
                $contract->studio_signature = $path;
                $contract->studio_signed_by = $data['signed_name'];
                $contract->studio_signed_user_id = Auth::id();
                $contract->studio_signed_at = now();
            }

            if ($contract->isFullySigned()) {
                $contract->status = BookingContractModel::STATUS_FULLY_SIGNED;
                $fullySigned = true;
            }

            $contract->save();
        });

        $booking->load('contract');
        $providerUserId = $this->contracts->providerUserId($booking);
        $providerRole = $booking->booking_type === 'studio' ? 'owner' : 'freelancer';

        if ($fullySigned) {
            $this->notifyContractSigned($booking, $booking->client_id, 'client');
            if ($providerUserId) {
                $this->notifyContractSigned($booking, $providerUserId, $providerRole);
            }

            // Both signatures exist: a paid booking can now confirm
            if ($booking->payment_status === BookingModel::PAYMENT_PAID) {
                $booking->confirmIfEligible();
            }
        } elseif ($party === 'studio') {
            $this->notifyContractReady($booking, $booking->client_id, 'client');
        } elseif ($providerUserId) {
            $this->notifyContractReady($booking, $providerUserId, $providerRole);
        }

        return response()->json([
            'success' => true,
            'message' => $fullySigned ? 'Contract signed by both parties.' : 'Your signature was saved. Waiting for the other party.',
            'fully_signed' => $fullySigned,
            'booking_status' => $booking->fresh()->status,
        ]);
    }

    /**
     * Provider's terms preview linked from the booking form.
     */
    public function terms($type, $id)
    {
        abort_unless(in_array($type, ['studio', 'freelancer'], true), 404);

        $template = $this->contracts->templateFor($type, $id);
        $html = $this->contracts->mergeToHtml($template, [
            'provider_name' => $this->contracts->providerNameFor($type, $id),
            'client_name' => 'you (the Client)',
            'booking_reference' => 'to be assigned',
            'package' => 'the selected package',
            'event_dates' => 'the booked date(s)',
            'event_time' => 'the booked time',
            'location' => 'the agreed location',
            'total' => 'the package total',
        ]);

        return view('contracts.terms', ['layout' => self::LAYOUTS['client'], 'html' => $html]);
    }

    /**
     * Work out which side of the contract the signed-in user is. Anyone else gets a 403.
     *
     * @return array{0: BookingModel, 1: string, 2: string}  booking, party (client|studio), role (client|owner|freelancer)
     */
    private function resolve($bookingId): array
    {
        $user = Auth::user();
        $booking = BookingModel::with('contract')->findOrFail($bookingId);

        if ($user->role === 'client' && $booking->client_id === $user->id) {
            return [$booking, 'client', 'client'];
        }

        if ($user->role === 'owner' && $booking->booking_type === 'studio'
            && StudiosModel::whereKey($booking->provider_id)->where('user_id', $user->id)->exists()) {
            return [$booking, 'studio', 'owner'];
        }

        if ($user->role === 'freelancer' && $booking->booking_type === 'freelancer' && (int) $booking->provider_id === $user->id) {
            return [$booking, 'studio', 'freelancer'];
        }

        abort(403, 'You are not a party to this contract.');
    }

    private function contractOf(BookingModel $booking): BookingContractModel
    {
        abort_unless($booking->requiresContract() && $booking->contract, 404, 'This booking has no contract.');

        return $booking->contract;
    }
}
