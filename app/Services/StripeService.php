<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeService
{
    protected $stripe;

    protected $secretKey;

    protected $publicKey;

    protected $isTestMode;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret_key');
        $this->publicKey = config('services.stripe.public_key');
        $this->isTestMode = config('services.stripe.mode', 'test') === 'test';

        $this->stripe = new StripeClient($this->secretKey);
    }

    /**
     * Create a checkout session for booking payments
     */
    public function createCheckoutSession($amount, $bookingReference, $currency = 'PHP', $description = 'Booking Payment')
    {
        try {
            // Redirect to verifyPayment first, which will then show success page
            $successUrl = route('client.payment.verify', ['reference' => $bookingReference]);
            $failedUrl = route('client.payment.failed', ['reference' => $bookingReference]);

            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $description,
                        ],
                        'unit_amount' => $amount * 100, // Convert to centavos
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $failedUrl,
                'metadata' => [
                    'booking_reference' => $bookingReference,
                ],
            ]);

            Log::info('Stripe Checkout Session Created', [
                'booking_reference' => $bookingReference,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'amount' => $amount,
                'success_url' => $successUrl,
            ]);

            return [
                'id' => $session->id,
                'url' => $session->url,
                'amount' => $amount,
                'currency' => $currency,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe Checkout Session Failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Create a checkout session for subscription payments
     */
    public function createSubscriptionCheckoutSession($amount, $subscriptionReference, $planName, $billingCycle, $currency = 'PHP', int $trialDays = 0)
    {
        try {
            $successUrl = route('owner.subscription.verify', ['reference' => $subscriptionReference]);
            $failedUrl = route('owner.subscription.failed', ['reference' => $subscriptionReference]);

            $description = "Subscription: {$planName} ({$billingCycle})";

            $interval = $billingCycle === 'yearly' ? 'year' : 'month';
            $params = [
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $description,
                            'description' => "{$planName} - {$billingCycle} subscription",
                        ],
                        'unit_amount' => $amount * 100, // Convert to centavos
                        'recurring' => ['interval' => $interval],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $failedUrl,
                'metadata' => [
                    'subscription_reference' => $subscriptionReference,
                    'plan_name' => $planName,
                    'billing_cycle' => $billingCycle,
                    'type' => 'subscription',
                ],
            ];

            if ($trialDays > 0) {
                $params['subscription_data'] = ['trial_period_days' => $trialDays];
                $params['payment_method_collection'] = 'if_required';
            }

            $session = $this->stripe->checkout->sessions->create($params);

            Log::info('Stripe Subscription Checkout Session Created', [
                'subscription_reference' => $subscriptionReference,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'amount' => $amount,
                'plan_name' => $planName,
                'trial_days' => $trialDays,
            ]);

            return [
                'id' => $session->id,
                'url' => $session->url,
                'amount' => $amount,
                'currency' => $currency,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe Subscription Checkout Session Failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function cancelSubscriptionAtPeriodEnd(string $subscriptionId)
    {
        try {
            $subscription = $this->stripe->subscriptions->update($subscriptionId, ['cancel_at_period_end' => true]);

            return $subscription->current_period_end ?? true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription cancellation failed', ['subscription_id' => $subscriptionId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function resumeSubscription(string $subscriptionId): bool
    {
        try {
            $this->stripe->subscriptions->update($subscriptionId, ['cancel_at_period_end' => false]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription resume failed', ['subscription_id' => $subscriptionId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Create a payment intent for more control
     */
    public function createPaymentIntent($amount, $bookingReference, $currency = 'PHP', $description = 'Booking Payment')
    {
        try {
            $intent = $this->stripe->paymentIntents->create([
                'amount' => $amount * 100,
                'currency' => $currency,
                'description' => $description,
                'metadata' => [
                    'booking_reference' => $bookingReference,
                ],
                'payment_method_types' => ['card'],
            ]);

            Log::info('Stripe Payment Intent Created', [
                'intent_id' => $intent->id,
                'client_secret' => $intent->client_secret,
                'booking_reference' => $bookingReference,
            ]);

            return [
                'id' => $intent->id,
                'client_secret' => $intent->client_secret,
                'amount' => $amount,
                'currency' => $currency,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Intent Failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Retrieve checkout session
     */
    public function retrieveCheckoutSession($sessionId)
    {
        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            Log::info('Stripe Checkout Session Retrieved', [
                'session_id' => $session->id,
                'status' => $session->status,
                'payment_status' => $session->payment_status,
            ]);

            return [
                'id' => $session->id,
                'status' => $session->status,
                'payment_status' => $session->payment_status,
                'amount_total' => $session->amount_total / 100,
                'metadata' => $session->metadata,
                'payment_intent' => $session->payment_intent,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe Retrieve Session Failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Retrieve payment intent
     */
    public function retrievePaymentIntent($intentId)
    {
        try {
            $intent = $this->stripe->paymentIntents->retrieve($intentId);

            Log::info('Stripe Payment Intent Retrieved', [
                'intent_id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount / 100,
            ]);

            return [
                'id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount / 100,
                'currency' => $intent->currency,
                'metadata' => $intent->metadata,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe Retrieve Intent Failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Test Stripe API connection
     */
    public function testConnection()
    {
        try {
            // Test by creating a simple payment intent
            $intent = $this->stripe->paymentIntents->create([
                'amount' => 1000, // 10 PHP
                'currency' => 'PHP',
                'description' => 'Test Connection',
                'payment_method_types' => ['card'],
            ]);

            return [
                'success' => true,
                'message' => 'Stripe API is working!',
                'mode' => $this->isTestMode ? 'Test Mode' : 'Live Mode',
                'test_card' => '4242424242424242',
                'test_expiry' => 'Any future date (e.g., 12/30)',
                'test_cvv' => 'Any 3 digits',
                'note' => $this->isTestMode ?
                    'Test mode: Use test cards only.' :
                    'Live mode: Real payments will be processed.',
            ];

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Stripe API test failed',
                'error' => $e->getMessage(),
                'mode' => $this->isTestMode ? 'Test Mode' : 'Live Mode',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
                'mode' => $this->isTestMode ? 'Test Mode' : 'Live Mode',
            ];
        }
    }

    /**
     * Get Stripe public key
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Get available payment methods for current mode
     */
    public function getAvailablePaymentMethods()
    {
        // Stripe supports card payments in test mode
        return ['card'];
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature, $secret)
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $secret
            );

            return $event;
        } catch (\Exception $e) {
            Log::error('Stripe webhook verification failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
