<?php

namespace App\Console\Commands;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Notifications\MaintenanceStatusUpdatedNotification;
use Illuminate\Console\Command;

class TestNotification extends Command
{
    protected $signature = 'test:notification {--type=tenant} {--userId=} {--requestId=}';
    protected $description = 'Send a test maintenance notification to a tenant or property manager';

    public function handle(): void
    {
        $type = $this->option('type');
        $userId = $this->option('userId');
        $requestId = $this->option('requestId');

        $req = $requestId
            ? MaintenanceRequest::withoutGlobalScopes()->find($requestId)
            : MaintenanceRequest::withoutGlobalScopes()->first();

        if (!$req) {
            $this->error('No maintenance request found.');
            return;
        }

        $this->info("Request: [{$req->id}] {$req->title} — status: {$req->status}");

        if ($type === 'manager') {
            $manager = $userId
                ? User::find($userId)
                : User::where('company_id', $req->company_id)->role('property_manager')->first();

            if (!$manager) {
                $this->error('No property manager found in the same company.');
                return;
            }

            $this->info("Manager: {$manager->name} (ID:{$manager->id}, Email:{$manager->email})");
            $manager->notify(new \App\Notifications\MaintenanceRequestNotification($req));
            $this->info('✅ New Maintenance Request Notification dispatched to queue for Manager!');
            $this->info("Listening channel: private-App.Models.User.{$manager->id}");
        } else {
            $tenant = $userId
                ? User::find($userId)
                : User::whereHas('roles', fn($q) => $q->where('name', 'tenant'))->first();

            if (!$tenant) {
                $this->error('No tenant found.');
                return;
            }

            $this->info("Tenant: {$tenant->name} (ID:{$tenant->id}, Email:{$tenant->email})");
            $tenant->notify(new \App\Notifications\MaintenanceStatusUpdatedNotification($req));
            $this->info('✅ Maintenance Status Updated Notification dispatched to queue for Tenant!');
            $this->info("Listening channel: private-App.Models.User.{$tenant->id}");
        }
    }
}
