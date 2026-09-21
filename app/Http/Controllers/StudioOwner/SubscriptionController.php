<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioOwner\SubscribeRequest;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Display a listing of available subscription plans.
     */
    public function index()
    {
        $plans = SubscriptionPlanModel::where('user_type', 'studio')
            ->where('status', 'active')
            ->orderBy('price')
            ->get();

        // Get current active subscription if any
        $currentSubscription = null;
        if (auth()->user()->studio) {
            $currentSubscription = StudioPlanModel::where('studio_id', auth()->user()->studio->id)
                ->currentlyActive()
                ->latest()
                ->first();
        }

        return view('owner.view-subscription-plans', compact('plans', 'currentSubscription'));
    }

    /**
     * Display the subscription status page.
     */
    public function status()
    {
        return view('owner.view-subscription-status');
    }

    /**
     * Get subscription plan details.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $plan = SubscriptionPlanModel::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => number_format($plan->price, 2),
                    'billing_cycle' => ucfirst($plan->billing_cycle),
                    'plan_type' => ucfirst($plan->plan_type),
                    'plan_code' => $plan->plan_code ?? 'N/A',
                    'priority_level' => $plan->priority_level ?? 0,
                    'features' => $plan->features,
                    'max_booking' => $plan->max_booking === null ? 'Unlimited' : $plan->max_booking,
                    'max_studio_photographers' => $plan->max_studio_photographers === null ? 'Unlimited' : $plan->max_studio_photographers,
                    'max_studios' => $plan->max_studios === null ? 'Unlimited' : $plan->max_studios,
                    'staff_limit' => $plan->staff_limit === null ? 'Unlimited' : $plan->staff_limit,
                    'support_level' => ucfirst($plan->support_level),
                    'commission_rate' => $plan->commission_rate.'%',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch subscription plan', [
                'error' => $e->getMessage(),
                'plan_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Subscription plan not found.',
            ], 404);
        }
    }

    /**
     * Initialize subscription payment.
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            // Check if user has a studio
            $studio = $user->studio;

            if (! $studio) {
                // Check if there are any studios owned by this user
                $studio = \App\Models\StudioOwner\StudiosModel::where('user_id', $user->id)->first();

                if (! $studio) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You need to create a studio first before subscribing to a plan.',
                        'redirect_to_studio_creation' => true,
                        'studio_creation_url' => route('owner.studio.create'),
                    ], 400);
                }
            }

            // Check if there's an active subscription
            $activeSubscription = StudioPlanModel::where('studio_id', $studio->id)
                ->currentlyActive()
                ->first();

            if ($activeSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active subscription. Please wait until it expires or cancel it first.',
                ], 400);
            }

            $plan = SubscriptionPlanModel::findOrFail($request->plan_id);

            // Generate subscription reference
            $subscriptionReference = StudioPlanModel::generateSubscriptionReference();

            // A studio may only ever use one free trial, even after cancelling.
            $trialAlreadyUsed = $this->hasUsedTrial($studio->id);

            // Free trial plans activate immediately with no charge
            if ($plan->trial_days > 0 && ! $trialAlreadyUsed) {
                $startsAt = now();
                $trialEndsAt = $startsAt->copy()->addDays($plan->trial_days);

                $studioPlan = StudioPlanModel::create([
                    'studio_id' => $studio->id,
                    'plan_id' => $plan->id,
                    'subscription_reference' => $subscriptionReference,
                    'start_date' => $startsAt,
                    'end_date' => $trialEndsAt,
                    'next_billing_date' => $trialEndsAt,
                    'amount_paid' => 0,
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'paid_at' => $startsAt,
                    'trial_ends_at' => $trialEndsAt,
                    'plan_snapshot' => $plan->toArray(),
                ]);

                // Keep the existing immediate access while Stripe owns the recurring trial.
                $checkoutSession = $this->stripeService->createSubscriptionCheckoutSession(
                    $plan->price,
                    $subscriptionReference,
                    $plan->name,
                    $plan->billing_cycle,
                    'PHP',
                    (int) $plan->trial_days
                );
                if (! $checkoutSession) {
                    throw new \RuntimeException('Failed to create Stripe trial checkout session');
                }
                $studioPlan->update(['stripe_session_id' => $checkoutSession['id']]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'trial' => true,
                    'message' => "Your {$plan->trial_days}-day free trial has started!",
                    'checkout_url' => $checkoutSession['url'] ?? null,
                ]);
            }

            // Create pending subscription
            $studioPlan = StudioPlanModel::create([
                'studio_id' => $studio->id,
                'plan_id' => $plan->id,
                'subscription_reference' => $subscriptionReference,
                'start_date' => now(),
                'end_date' => $this->calculateEndDate($plan->billing_cycle),
                'next_billing_date' => $this->calculateEndDate($plan->billing_cycle),
                'amount_paid' => $plan->price,
                'payment_status' => 'pending',
                'status' => 'pending',
                'plan_snapshot' => $plan->toArray(),
            ]);

            // Create Stripe checkout session using the dedicated subscription method
            $checkoutSession = $this->stripeService->createSubscriptionCheckoutSession(
                $plan->price,
                $subscriptionReference,
                $plan->name,
                $plan->billing_cycle,
                'PHP'
            );

            if (! $checkoutSession) {
                throw new \Exception('Failed to create Stripe checkout session');
            }

            // Update subscription with Stripe session ID
            $studioPlan->update([
                'stripe_session_id' => $checkoutSession['id'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'trial' => false,
                'message' => ($plan->trial_days > 0 && $trialAlreadyUsed)
                    ? 'Your free trial has already been used. Continuing to payment...'
                    : 'Redirecting to payment...',
                'checkout_url' => $checkoutSession['url'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to initialize subscription', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'plan_id' => $request->plan_id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process subscription. Please try again.',
            ], 500);
        }
    }

    /**
     * Get subscription history.
     */
    public function history(): JsonResponse
    {
        try {
            $user = auth()->user();

            // Get the studio - check both relations
            $studio = $user->studio;

            if (! $studio) {
                // Try to find studio directly
                $studio = \App\Models\StudioOwner\StudiosModel::where('user_id', $user->id)->first();
            }

            // If still no studio, return empty history instead of error
            if (! $studio) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $subscriptions = StudioPlanModel::with('plan')
                ->where('studio_id', $studio->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'subscription_reference' => $subscription->subscription_reference,
                        'plan_name' => $subscription->plan->name ?? 'Unknown Plan',
                        'amount' => number_format($subscription->amount_paid, 2),
                        'start_date' => $subscription->start_date->format('M d, Y'),
                        'end_date' => $subscription->end_date->format('M d, Y'),
                        'payment_status' => $subscription->payment_status,
                        'payment_status_badge' => $this->getPaymentStatusBadge($subscription->payment_status),
                        'status' => $subscription->status,
                        'status_badge' => $this->getStatusBadge($subscription->status),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $subscriptions,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch subscription history', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription history.',
            ], 500);
        }
    }

    /**
     * Calculate end date based on billing cycle.
     */
    private function calculateEndDate(string $billingCycle)
    {
        switch ($billingCycle) {
            case 'monthly':
                return now()->addMonth();
            case 'yearly':
                return now()->addYear();
            default:
                return now()->addMonth();
        }
    }

    /**
     * Get payment status badge.
     */
    private function getPaymentStatusBadge(string $status): string
    {
        $classes = [
            'pending' => 'badge-soft-warning',
            'paid' => 'badge-soft-success',
            'failed' => 'badge-soft-danger',
            'refunded' => 'badge-soft-danger',
        ];

        $labels = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];

        $class = $classes[$status] ?? 'badge-soft-secondary';
        $label = $labels[$status] ?? ucfirst($status);

        return "<span class='p-1 w-100 badge {$class}'>{$label}</span>";
    }

    /**
     * Get status badge.
     */
    private function getStatusBadge(string $status): string
    {
        $classes = [
            'active' => 'badge-soft-primary',
            'expired' => 'badge-soft-danger',
            'cancelled' => 'badge-soft-danger',
            'pending' => 'badge-soft-warning',
        ];

        $labels = [
            'active' => 'Active',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'pending' => 'Pending',
        ];

        $class = $classes[$status] ?? 'badge-soft-secondary';
        $label = $labels[$status] ?? ucfirst($status);

        return "<span class='p-1 w-100 badge {$class}'>{$label}</span>";
    }

    /**
     * Handle successful payment
     */
    public function paymentSuccess(string $reference)
    {
        $studioPlan = StudioPlanModel::where('subscription_reference', $reference)->first();

        if (! $studioPlan) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'Subscription not found.');
        }

        return view('owner.subscription-success', [
            'subscription' => $studioPlan,
            'plan' => $studioPlan->plan,
        ]);
    }

    /**
     * Handle failed payment
     */
    public function paymentFailed(string $reference)
    {
        $studioPlan = StudioPlanModel::where('subscription_reference', $reference)->first();

        return view('owner.subscription-failed', [
            'subscription' => $studioPlan,
            'error' => session('error', 'Payment was cancelled or failed.'),
        ]);
    }

    /**
     * Get subscription status data for DataTable.
     */
    public function getStatusData(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Get the studio
            $studio = $user->studio;
            if (! $studio) {
                $studio = \App\Models\StudioOwner\StudiosModel::where('user_id', $user->id)->first();
            }

            if (! $studio) {
                return response()->json([
                    'draw' => intval($request->input('draw', 1)),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                ]);
            }

            $query = StudioPlanModel::with('plan')
                ->where('studio_id', $studio->id);

            // Search
            if ($request->has('search') && ! empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('subscription_reference', 'like', "%{$search}%")
                        ->orWhereHas('plan', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Filter by status
            if ($request->has('status') && ! empty($request->status)) {
                $query->where('status', $request->status);
            }

            $totalRecords = $query->count();

            // Ordering
            $columns = ['subscription_reference', 'plan_name', 'amount_paid', 'start_date', 'end_date', 'payment_status', 'status'];
            $orderColumnIndex = $request->input('order.0.column', 0);
            $orderDirection = $request->input('order.0.dir', 'desc');

            if (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $subscriptions = $query->skip($start)->take($length)->get();

            // Format data for DataTable
            $data = $subscriptions->map(function ($subscription) {
                // Use the model's canBeCancelled method
                $canCancel = $subscription->canBeCancelled();

                $cancelButton = '';
                if ($canCancel) {
                    $cancelButton = '<button class="btn btn-sm btn-soft-danger cancel-subscription-btn" 
                                            data-id="'.$subscription->id.'"
                                            data-reference="'.$subscription->subscription_reference.'"
                                            data-plan="'.($subscription->plan->name ?? 'Unknown').'">
                                        <i class="ti ti-x"></i> Cancel
                                    </button>';
                } elseif ($subscription->scheduled_cancellation_at) {
                    $cancelButton = '<button class="btn btn-sm btn-soft-success resume-subscription-btn" data-id="'.$subscription->id.'">
                                        <i class="ti ti-player-play"></i> Resume
                                    </button>';
                }

                return [
                    'subscription_reference' => '<span class="fw-medium font-monospace">'.$subscription->subscription_reference.'</span>',
                    'plan_name' => $subscription->plan->name ?? 'Unknown Plan',
                    'amount' => '₱'.number_format($subscription->amount_paid, 2),
                    'start_date' => $subscription->start_date->format('M d, Y'),
                    'end_date' => $subscription->end_date->format('M d, Y'),
                    'payment_status' => $this->getPaymentStatusBadge($subscription->payment_status),
                    'status' => $this->getStatusBadge($subscription->status),
                    'actions' => '<div class="d-flex justify-content-center gap-1">'.$cancelButton.'</div>',
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch subscription status data', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ], 500);
        }
    }

    /**
     * Get subscription details for modal via AJAX.
     */
    public function getSubscriptionDetails(string $id): JsonResponse
    {
        try {
            $user = auth()->user();

            // Get the studio
            $studio = $user->studio;
            if (! $studio) {
                $studio = \App\Models\StudioOwner\StudiosModel::where('user_id', $user->id)->first();
            }

            if (! $studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Studio not found.',
                ], 400);
            }

            $subscription = StudioPlanModel::with('plan')
                ->where('id', $id)
                ->where('studio_id', $studio->id)
                ->first();

            if (! $subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found.',
                ], 404);
            }

            // Check if can be cancelled
            $canCancel = $subscription->canBeCancelled();
            $cancelDeadline = $subscription->getCancellationDeadline();

            // Calculate days since subscription started
            $referenceDate = $subscription->paid_at ?? $subscription->start_date;
            $daysSinceStart = now()->diffInDays($referenceDate, false);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $subscription->id,
                    'reference' => $subscription->subscription_reference,
                    'plan_name' => $subscription->plan->name ?? 'Unknown Plan',
                    'plan_type' => $subscription->plan->plan_type ?? 'N/A',
                    'billing_cycle' => ucfirst($subscription->plan->billing_cycle ?? 'N/A'),
                    'amount' => '₱'.number_format($subscription->amount_paid, 2),
                    'amount_raw' => $subscription->amount_paid,
                    'start_date' => $subscription->start_date->format('M d, Y'),
                    'start_date_raw' => $subscription->start_date->format('Y-m-d'),
                    'end_date' => $subscription->end_date->format('M d, Y'),
                    'end_date_raw' => $subscription->end_date->format('Y-m-d'),
                    'payment_status' => $subscription->payment_status,
                    'payment_status_label' => ucfirst($subscription->payment_status),
                    'status' => $subscription->status,
                    'status_label' => ucfirst($subscription->status),
                    'created_at' => $subscription->created_at->format('M d, Y'),
                    'paid_at' => $subscription->paid_at ? $subscription->paid_at->format('Y-m-d H:i:s') : null,
                    'can_cancel' => $canCancel,
                    'cancel_deadline' => $cancelDeadline->format('M d, Y'),
                    'cancel_deadline_raw' => $cancelDeadline->format('Y-m-d'),
                    'days_since_start' => $daysSinceStart,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch subscription details', [
                'error' => $e->getMessage(),
                'subscription_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load subscription details. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify payment after Stripe redirect
     */
    public function verifyPayment(Request $request, string $reference)
    {
        if (! $request->get('session_id') || ! StudioPlanModel::where('subscription_reference', $reference)->exists()) {
            return redirect()->route('owner.subscription.failed', ['reference' => $reference])
                ->with('error', 'Payment is awaiting provider confirmation.');
        }

        return redirect()->route('owner.subscription.success', ['reference' => $reference])
            ->with('status', 'Payment received. Stripe confirmation will update access shortly.');
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        try {
            $studio = $this->studioForUser();
            if (! $studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Studio not found.',
                ], 400);
            }

            $subscription = StudioPlanModel::whereKey($id)
                ->where('studio_id', $studio->id)
                ->first();

            if (! $subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found.',
                ], 404);
            }

            if (! in_array($subscription->status, ['active', 'grace'], true)
                || $subscription->payment_status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only an accessible paid subscription can be cancelled.',
                ], 400);
            }

            $providerPeriodEnd = $subscription->stripe_subscription_id
                ? $this->stripeService->cancelSubscriptionAtPeriodEnd($subscription->stripe_subscription_id)
                : true;
            if ($providerPeriodEnd === false) {
                return response()->json(['success' => false, 'message' => 'Stripe could not schedule cancellation.'], 502);
            }

            $reason = $request->input('reason', 'Cancelled by user');
            $scheduledAt = is_numeric($providerPeriodEnd)
                ? now()->setTimestamp((int) $providerPeriodEnd)
                : ($subscription->end_date?->copy()->endOfDay() ?? now());
            // Once Stripe has accepted the cancellation, revoke local access immediately.
            // Keep the provider's period end so the record remains an accurate billing history.
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'scheduled_cancellation_at' => $scheduledAt,
                'cancellation_reason' => $reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully.',
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to cancel subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription. Please try again.',
            ], 500);
        }
    }

    public function resume(Request $request, string $id): JsonResponse
    {
        $studio = $this->studioForUser();
        $subscription = $studio
            ? StudioPlanModel::whereKey($id)->where('studio_id', $studio->id)->first()
            : null;

        if (! $subscription) {
            return response()->json(['success' => false, 'message' => 'Subscription not found.'], 404);
        }

        if ($subscription->scheduled_cancellation_at && $subscription->scheduled_cancellation_at->isFuture()) {
            if ($subscription->stripe_subscription_id
                && ! $this->stripeService->resumeSubscription($subscription->stripe_subscription_id)) {
                return response()->json(['success' => false, 'message' => 'Stripe could not resume the subscription.'], 502);
            }
            // A successful provider resume makes the existing paid period active again.
            $subscription->update([
                'status' => 'active',
                'payment_status' => 'paid',
                'cancelled_at' => null,
                'scheduled_cancellation_at' => null,
                'cancellation_reason' => null,
            ]);

            return response()->json(['success' => true, 'message' => 'Subscription cancellation was resumed.']);
        }

        if (! in_array($subscription->status, ['expired', 'cancelled'], true)) {
            return response()->json(['success' => false, 'message' => 'Subscription is already active.'], 400);
        }

        $plan = $subscription->plan;
        if (! $plan || $plan->status !== 'active' || $plan->user_type !== 'studio') {
            $plan = SubscriptionPlanModel::where('user_type', 'studio')->where('status', 'active')->orderBy('price')->first();
        }
        if (! $plan) {
            return response()->json(['success' => false, 'message' => 'No active studio plan is available.'], 409);
        }

        $reference = StudioPlanModel::generateSubscriptionReference();
        $renewal = StudioPlanModel::create([
            'studio_id' => $studio->id,
            'plan_id' => $plan->id,
            'subscription_reference' => $reference,
            'start_date' => now(),
            'end_date' => $this->calculateEndDate($plan->billing_cycle),
            'next_billing_date' => $this->calculateEndDate($plan->billing_cycle),
            'amount_paid' => $plan->price,
            'payment_status' => 'pending',
            'status' => 'pending',
            'plan_snapshot' => $plan->toArray(),
        ]);
        $checkout = $this->stripeService->createSubscriptionCheckoutSession(
            $plan->price, $reference, $plan->name, $plan->billing_cycle, 'PHP'
        );
        if (! $checkout) {
            $renewal->delete();

            return response()->json(['success' => false, 'message' => 'Failed to create the reactivation checkout.'], 502);
        }
        $renewal->update(['stripe_session_id' => $checkout['id']]);

        return response()->json(['success' => true, 'checkout_url' => $checkout['url'], 'subscription_id' => $renewal->id]);
    }

    private function studioForUser(): ?StudiosModel
    {
        $user = auth()->user();

        return $user?->studio ?: StudiosModel::where('user_id', $user?->id)->first();
    }

    /**
     * Determine whether the studio has ever consumed a free trial.
     *
     * Trial history is recorded either on the trial_ends_at column or, for
     * legacy rows, inside the plan_snapshot captured at subscription time.
     */
    private function hasUsedTrial(int $studioId): bool
    {
        $subscriptions = StudioPlanModel::where('studio_id', $studioId)
            ->get(['id', 'trial_ends_at', 'plan_snapshot']);

        foreach ($subscriptions as $subscription) {
            if ($subscription->trial_ends_at !== null) {
                return true;
            }

            $snapshot = $subscription->plan_snapshot;
            if (is_array($snapshot) && (int) ($snapshot['trial_days'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
