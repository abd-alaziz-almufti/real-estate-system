<?php
// test_notify.php — run with: php artisan tinker < test_notify.php
// OR: php -r "require 'bootstrap/app.php';" -- not ideal
// We'll use: php artisan tinker --execute="..." via a file

use App\Models\User;
use App\Models\MaintenanceRequest;
use App\Notifications\MaintenanceStatusUpdatedNotification;

// Find first tenant user
$tenant = User::whereHas('roles', fn($q) => $q->where('name', 'tenant'))->first();

if (!$tenant) {
    echo "ERROR: No tenant user found.\n";
    return;
}

echo "Tenant found: ID={$tenant->id}, Email={$tenant->email}\n";
echo "Company ID: {$tenant->company_id}\n";

// Find a maintenance request in same company
$req = MaintenanceRequest::withoutGlobalScopes()
    ->where('company_id', $tenant->company_id)
    ->first();

if (!$req) {
    echo "ERROR: No maintenance request found for this company.\n";
    return;
}

echo "Request found: ID={$req->id}, Title={$req->title}, Status={$req->status}\n";

// Send notification
$tenant->notify(new MaintenanceStatusUpdatedNotification($req));

echo "SUCCESS: Notification dispatched!\n";
echo "Check DB: SELECT * FROM notifications ORDER BY created_at DESC LIMIT 1;\n";
echo "Check jobs: SELECT COUNT(*) FROM jobs;\n";
