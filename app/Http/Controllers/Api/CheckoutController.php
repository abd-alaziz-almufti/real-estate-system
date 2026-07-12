<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function createSession(Request $request)
    {

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);


        $user = $request->user();

        $plan = Plan::findOrFail($request->plan_id);

        if (!$plan->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'The selected plan is not active.'
            ], 400);
        }

        // Use company_id if the user belongs to a company, otherwise use user id.
        // The webhook will create/update the subscription based on client_reference_id.
        $referenceId = $user->company_id ? (string) $user->company_id : 'user_' . $user->id;

        try {
            $session = $this->stripeService->createCheckoutSession($plan, $referenceId, (string) $user->id);

            return response()->json([
                'success' => true,
                'url' => $session->url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe Session Creation Failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
