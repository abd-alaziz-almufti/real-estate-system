<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->verifyWebhook($payload, $sigHeader);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $this->processSuccessfulPayment($session);
        }

        return response()->json(['status' => 'success'], 200);
    }

    protected function processSuccessfulPayment($session)
    {
        $companyId = $session->client_reference_id;
        $planId = $session->metadata->plan_id ?? null;

        if ($companyId && $planId) {
            $plan = Plan::find($planId);
            if ($plan) {
                $startsAt = now();
                $endsAt = strtolower($plan->billing_cycle) === 'yearly' 
                    ? now()->addYear() 
                    : now()->addMonth();

                $subscription = Subscription::updateOrCreate(
                    ['company_id' => $companyId],
                    [
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'trial_ends_at' => null,
                        'canceled_at' => null,
                    ]
                );

                SubscriptionPayment::create([
                    'subscription_id' => $subscription->id,
                    'amount' => $session->amount_total / 100,
                    'currency' => strtoupper($session->currency),
                    'payment_method' => 'stripe_card',
                    'status' => 'paid',
                    'transaction_reference' => $session->payment_intent ?? $session->id,
                    'paid_at' => now(),
                ]);
            }
        }
    }
}
