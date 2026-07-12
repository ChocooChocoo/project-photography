<?php

namespace Tests\Feature\Payment;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    /**
     * It registers unauthenticated, CSRF-exempt webhook routes.
     */
    public function test_webhook_routes_are_registered_and_csrf_exempt(): void
    {
        $this->assertTrue(Route::has('webhook.paymongo'));
        $this->assertTrue(Route::has('webhook.stripe'));

        // No CSRF token sent — a 419 would mean the exemption isn't wired.
        $response = $this->postJson('/webhook/paymongo', ['data' => ['attributes' => ['type' => 'unknown']]]);
        $this->assertNotEquals(419, $response->status());
    }

    /**
     * It rejects a Paymongo webhook with a missing/invalid signature.
     */
    public function test_paymongo_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/webhook/paymongo', [
            'data' => ['id' => 'evt_test', 'attributes' => ['type' => 'checkout_session.paid']],
        ], ['Paymongo-Signature' => 't=123,te=bogus']);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid signature']);
    }
}
