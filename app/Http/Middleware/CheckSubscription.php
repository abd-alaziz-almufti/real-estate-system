<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Always allow Super Admins to access the system
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // 2. Get the user's company
        $company = $user->company;

        if (!$company) {
            // If the user has no company (and is not a super admin), something is wrong.
            // But for safety, we'll allow them through or let Filament handle it.
            return $next($request);
        }

        // 4. Check if the company is active
        if (!$company->is_active) {
            return response()->view('errors.subscription-required', [
                'message' => 'Your company account is currently inactive. Please contact support.',
            ]);
        }

        // 5. Check if the company has an active subscription
        if (!$company->hasActiveSubscription()) {
            return response()->view('errors.subscription-required', [
                'message' => 'Your subscription has expired or is inactive. Please upgrade your plan to continue.',
            ]);
        }

        return $next($request);
    }
}
