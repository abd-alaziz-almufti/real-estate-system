<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::first();
$company = \App\Models\Company::first();
$unit = \App\Models\Unit::first();

// Create a dummy lease for exactly 1 year
$lease = \App\Models\Lease::create([
    'company_id' => $company->id,
    'unit_id' => $unit->id,
    'tenant_id' => $user->id,
    'start_date' => '2026-05-20',
    'end_date' => '2027-05-20',
    'rent_amount' => 3600.00,
    'payment_frequency' => 'monthly',
    'payment_day' => 1,
    'status' => 'draft',
]);

// Mark active and generate
$lease->update(['status' => 'active']);
$lease->generatePaymentSchedule();

echo "Lease generated with " . $lease->payments()->count() . " payments!\n";
foreach ($lease->payments as $payment) {
    echo "- Due: {$payment->due_date->toDateString()}, Amount: {$payment->amount}\n";
}

// Clean up
$lease->forceDelete();
