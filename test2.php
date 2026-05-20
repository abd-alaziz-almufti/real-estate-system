<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$start = \Carbon\Carbon::parse('2026-05-20');
$end = \Carbon\Carbon::parse('2027-05-20');
$monthsCount = $start->diffInMonths($end);
if ($start->copy()->addMonths($monthsCount)->lt($end)) {
    $monthsCount++;
}
$monthsCount = max(1, $monthsCount);
echo "12 months * 300 = " . round($monthsCount * 300, 2) . "\n";
echo "actual months = " . $monthsCount . "\n";

foreach (\App\Models\Unit::all() as $u) {
    if (abs((float)$u->rent_price - 300) < 0.1) {
        echo "Found unit with ~300.00: id={$u->id}, actual={$u->rent_price}\n";
    }
    if (abs((float)$u->rent_price - 324.71) < 1) {
        echo "Found unit with ~324.71: id={$u->id}, actual={$u->rent_price}\n";
    }
    if ((string)$u->rent_price === '3896.61' || abs((float)$u->rent_price - 3896.61) < 1) {
        echo "Found unit with 3896.61: id={$u->id}\n";
    }
}
