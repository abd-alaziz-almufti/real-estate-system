<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$unit = \App\Models\Unit::find(7); // some unit
echo "Unit 7 price: " . $unit->rent_price . "\n";

$start = \Carbon\Carbon::parse('2026-05-20');
$end = \Carbon\Carbon::parse('2027-05-20');
$monthsCount = $start->diffInMonths($end);
if ($start->copy()->addMonths($monthsCount)->lt($end)) {
    $monthsCount++;
}
$monthsCount = max(1, $monthsCount);
echo "12 months * 300 = " . round($monthsCount * 300, 2) . "\n";

// check if any unit has a price that is fractional
foreach (\App\Models\Unit::all() as $u) {
    if (strpos((string)$u->rent_price, '.') !== false && substr((string)$u->rent_price, -2) !== '00') {
        echo "Unit {$u->id} has fractional price: {$u->rent_price}\n";
    }
}
