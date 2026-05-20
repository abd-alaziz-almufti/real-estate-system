<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (\App\Models\Lease::all() as $lease) {
    if ((string)$lease->rent_amount === '3896.61' || abs((float)$lease->rent_amount - 3896.61) < 0.1) {
        echo "Found lease with 3896.61: id={$lease->id}, start={$lease->start_date}, end={$lease->end_date}, unit={$lease->unit_id}\n";
    }
}
