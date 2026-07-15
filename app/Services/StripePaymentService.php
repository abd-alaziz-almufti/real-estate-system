<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripePaymentService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createSubscriptionCheckoutSession(Plan $plan, string $companyId, string $userId)
    {
        // Retrieve the hardcoded price ID from config if it exists
        $priceId = config("services.stripe.prices.{$plan->slug}");

        if ($priceId) {
            // Using predefined Stripe Product/Price
            $lineItems = [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ]
            ];
            $mode = 'subscription'; // Using Stripe Billing for recurring payment
        } else {
            // Fallback for dynamic pricing if no ID matched
            $lineItems = [
                [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $plan->name,
                            'description' => $plan->description ?? 'Subscription plan: ' . $plan->name,
                        ],
                        'unit_amount' => (int) ($plan->price * 100),
                    ],
                    'quantity' => 1,
                ]
            ];
            $mode = 'payment'; // One-time checkout
        }

        return $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => $mode,
            'success_url' => rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/') . '/checkout/cancel?plan=' . $plan->slug,
            'client_reference_id' => $companyId, // optional to get more secure.
            'metadata' => [
                'plan_id' => $plan->id,
                'user_id' => $userId,
            ],
        ]);
    }


    public function createLeaseCheckoutSession(\App\Models\Unit $unit, string $userId)
    {
        $rentPrice = (float) $unit->rent_price;

        $lineItems = [
            [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Rent Payment - Unit ' . $unit->unit_number,
                        'description' => 'First month rent payment / lease application fee for unit ' . $unit->unit_number . ' at ' . ($unit->property->name ?? 'Property'),
                    ],
                    'unit_amount' => (int) ($rentPrice * 100),
                ],
                'quantity' => 1,
            ]
        ];

        return $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}&type=lease',
            'cancel_url' => rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/') . '/units/' . $unit->id,
            'client_reference_id' => 'lease_' . $unit->id,
            'metadata' => [
                'unit_id' => $unit->id,
                'user_id' => $userId,
                'type' => 'lease',
            ],
        ]);
    }
    public function createPaymentCheckoutSession(Payment $payment, string $userId)
    {
        $remainingAmount = $payment->remaining_amount;
        $lineItems = [
            [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => ' Rent Payment - ' . $payment?->lease?->unit?->property?->name . ' Unit ' . $payment->lease->unit->unit_number,
                        'description' => 'Rent payment for unit ' . $payment?->lease?->unit?->unit_number . ' at ' . $payment->lease->unit->property->name,
                    ],
                    'unit_amount' => (int) ($remainingAmount * 100),
                ],
                'quantity' => 1,
            ]
        ];

        return $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}&type=payment',
            'cancel_url' => rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/') . '/tenant/payments',
            'client_reference_id' => 'payment_' . $payment->id,
            'metadata' => [
                'payment_id' => $payment->id,
                'user_id' => $userId,
                'type' => 'payment',
            ],
        ]);
    }

    public function verifyWebhook(string $payload, string $signature)
    {
        $webhookSecret = config('services.stripe.webhook_secret');
        return Webhook::constructEvent($payload, $signature, $webhookSecret);
    }

    public function retrieveSession(string $sessionId)
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId);
    }
}
