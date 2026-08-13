<?php

namespace App\Http\Controllers;

use App\Models\StudioPlanModel;
use App\Models\SystemRevenueModel;
use App\Services\StripeService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeSubscriptionWebhookController extends Controller
{
    public function __construct(private StripeService $stripeService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $event = $this->stripeService->verifyWebhookSignature(
            $request->getContent(),
            (string) $request->header('Stripe-Signature'),
            (string) config('services.stripe.subscription_webhook_secret')
        );
        if (! $event) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $object = json_decode(json_encode($event->data->object), true) ?: [];
        try {
            $handled = match ($event->type) {
                'checkout.session.completed' => $this->checkoutCompleted($object),
                'invoice.paid' => $this->invoicePaid($object),
                'invoice.payment_failed' => $this->invoiceFailed($object),
                'customer.subscription.updated' => $this->subscriptionUpdated($object),
                'customer.subscription.deleted' => $this->subscriptionDeleted($object),
                default => true,
            };
            if ($handled === false) {
                $this->defer($event->id, $event->type, $object);
            }
        } catch (\Throwable $e) {
            Log::error('Stripe subscription webhook processing failed', [
                'event_id' => $event->id ?? null,
                'event_type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }

        return response()->json(['received' => true]);
    }

    private function checkoutCompleted(array $session): bool
    {
        $reference = data_get($session, 'metadata.subscription_reference');
        $subscription = StudioPlanModel::where('subscription_reference', $reference)
            ->when(data_get($session, 'id'), fn ($query, $id) => $query->orWhere('stripe_session_id', $id))
            ->latest('id')->first();
        if (! $subscription || ! in_array(data_get($session, 'payment_status'), ['paid', 'no_payment_required'], true)) {
            return true;
        }

        $subscription->update([
            'status' => 'active',
            'payment_status' => 'paid',
            'stripe_customer_id' => data_get($session, 'customer'),
            'stripe_subscription_id' => data_get($session, 'subscription'),
            'stripe_response' => $session,
            'paid_at' => $subscription->paid_at ?: now(),
        ]);
        $this->reconcileDeferred($subscription);

        return true;
    }

    private function invoicePaid(array $invoice): bool
    {
        $invoiceId = data_get($invoice, 'id');
        if (! $invoiceId || StudioPlanModel::where('stripe_invoice_id', $invoiceId)->exists()) {
            return true;
        }

        $current = StudioPlanModel::where('stripe_subscription_id', data_get($invoice, 'subscription'))
            ->latest('id')->first();
        if (! $current && data_get($invoice, 'metadata.subscription_reference')) {
            $current = StudioPlanModel::where('subscription_reference', data_get($invoice, 'metadata.subscription_reference'))->latest('id')->first();
        }
        if (! $current) {
            return false;
        }

        $start = $this->dateFromTimestamp(data_get($invoice, 'period_start')) ?: now()->startOfDay();
        $end = $this->dateFromTimestamp(data_get($invoice, 'period_end')) ?: $start->copy()->addMonth();
        $amount = ((int) data_get($invoice, 'amount_paid', 0)) / 100;

        try {
            DB::transaction(function () use ($current, $invoice, $invoiceId, $start, $end, $amount) {
                $period = $current->status === 'pending' && ! $current->stripe_invoice_id
                ? $current
                : StudioPlanModel::create([
                    'studio_id' => $current->studio_id,
                    'plan_id' => $current->plan_id,
                    'subscription_reference' => StudioPlanModel::generateSubscriptionReference(),
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'next_billing_date' => $end->toDateString(),
                    'amount_paid' => $amount,
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'plan_snapshot' => $current->plan_snapshot,
                ]);

                $period->update([
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'next_billing_date' => $end->toDateString(),
                    'amount_paid' => $amount,
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'stripe_customer_id' => data_get($invoice, 'customer', $current->stripe_customer_id),
                    'stripe_subscription_id' => data_get($invoice, 'subscription', $current->stripe_subscription_id),
                    'stripe_invoice_id' => $invoiceId,
                    'stripe_response' => $invoice,
                    'paid_at' => now(),
                    'grace_ends_at' => null,
                    'first_failure_at' => null,
                    'stripe_first_failure_invoice_id' => null,
                ]);
                if (! SystemRevenueModel::createForSubscription($period, $period->studio)) {
                    throw new \RuntimeException('Subscription revenue could not be recorded.');
                }
            });
        } catch (UniqueConstraintViolationException) {
            // Concurrent delivery won the unique invoice race; its committed row is authoritative.
            if (! SystemRevenueModel::where('stripe_invoice_id', $invoiceId)->exists()) {
                throw new \RuntimeException('Duplicate invoice was committed without revenue.');
            }
        }

        return true;
    }

    private function invoiceFailed(array $invoice): bool
    {
        $subscription = StudioPlanModel::where('stripe_subscription_id', data_get($invoice, 'subscription'))
            ->latest('id')->first();
        if (! $subscription && data_get($invoice, 'metadata.subscription_reference')) {
            $subscription = StudioPlanModel::where('subscription_reference', data_get($invoice, 'metadata.subscription_reference'))->latest('id')->first();
        }
        if (! $subscription) {
            return false;
        }

        $failedPeriodEnd = $this->dateFromTimestamp(data_get($invoice, 'period_end'));
        $newerPaid = StudioPlanModel::where('stripe_subscription_id', $subscription->stripe_subscription_id)
            ->where('payment_status', 'paid')
            ->where('stripe_invoice_id', '!=', data_get($invoice, 'id'))
            ->when($failedPeriodEnd, fn ($query) => $query->whereDate('end_date', '>=', $failedPeriodEnd->toDateString()))
            ->exists();
        if ($newerPaid) {
            return true;
        }

        if ($subscription->stripe_invoice_id === data_get($invoice, 'id')
            && $subscription->payment_status === 'paid') {
            return true;
        }

        $updates = [];
        if (! $subscription->first_failure_at) {
            $updates += [
                'status' => 'grace',
                'first_failure_at' => now(),
                'stripe_first_failure_invoice_id' => data_get($invoice, 'id'),
                'grace_ends_at' => $subscription->graceDeadline(),
            ];
        }
        $subscription->update($updates);

        return true;
    }

    private function subscriptionUpdated(array $stripeSubscription): bool
    {
        $subscription = StudioPlanModel::where('stripe_subscription_id', data_get($stripeSubscription, 'id'))
            ->latest('id')->first();
        if (! $subscription) {
            return false;
        }
        $periodEnd = $this->dateFromTimestamp(data_get($stripeSubscription, 'current_period_end'));
        $updates = [
            'stripe_customer_id' => data_get($stripeSubscription, 'customer', $subscription->stripe_customer_id),
            'scheduled_cancellation_at' => data_get($stripeSubscription, 'cancel_at_period_end') && $periodEnd
                ? $periodEnd : null,
        ];
        if ($periodEnd) {
            $updates['end_date'] = $periodEnd->toDateString();
            $updates['next_billing_date'] = $periodEnd->toDateString();
        }
        if (data_get($stripeSubscription, 'status') === 'active'
            && ! $subscription->first_failure_at
            && $subscription->status !== 'grace') {
            $updates['status'] = 'active';
            $updates['payment_status'] = 'paid';
        }
        $subscription->update($updates);

        return true;
    }

    private function subscriptionDeleted(array $stripeSubscription): bool
    {
        $exists = StudioPlanModel::where('stripe_subscription_id', data_get($stripeSubscription, 'id'))->exists();
        if (! $exists) {
            return false;
        }

        StudioPlanModel::where('stripe_subscription_id', data_get($stripeSubscription, 'id'))
            ->whereIn('status', ['active', 'grace', 'pending'])
            ->update(['status' => 'expired', 'scheduled_cancellation_at' => null, 'cancelled_at' => now()]);

        return true;
    }

    private function defer(string $eventId, string $eventType, array $object): void
    {
        DB::table('tbl_stripe_subscription_webhook_events')->insertOrIgnore([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'stripe_subscription_id' => data_get($object, 'subscription') ?: data_get($object, 'id'),
            'stripe_customer_id' => data_get($object, 'customer'),
            'stripe_invoice_id' => data_get($object, 'id'),
            'payload' => json_encode($object),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function reconcileDeferred(StudioPlanModel $subscription): void
    {
        DB::table('tbl_stripe_subscription_webhook_events')
            ->where(function ($query) use ($subscription) {
                $query->where('stripe_subscription_id', $subscription->stripe_subscription_id)
                    ->orWhere('stripe_customer_id', $subscription->stripe_customer_id);
            })
            ->get()
            ->each(function ($event) {
                $object = json_decode($event->payload, true) ?: [];
                $handled = match ($event->event_type) {
                    'invoice.paid' => $this->invoicePaid($object),
                    'invoice.payment_failed' => $this->invoiceFailed($object),
                    'customer.subscription.updated' => $this->subscriptionUpdated($object),
                    'customer.subscription.deleted' => $this->subscriptionDeleted($object),
                    default => true,
                };
                if ($handled) {
                    DB::table('tbl_stripe_subscription_webhook_events')->where('id', $event->id)->delete();
                }
            });
    }

    private function dateFromTimestamp($timestamp): ?Carbon
    {
        return $timestamp ? Carbon::createFromTimestamp((int) $timestamp, config('app.timezone')) : null;
    }
}
