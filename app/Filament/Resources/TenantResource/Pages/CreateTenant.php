<?php
// app/Filament/Resources/TenantResource/Pages/CreateTenant.php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    // 🔥 CRITICAL: Custom creation logic
    // WHY: We need to create User FIRST, then Tenant
    protected function handleRecordCreation(array $data): Model
    {
        // ✅ WHY: Use database transaction for safety
        // If User creation fails, Tenant won't be created (data integrity)
        // If Tenant creation fails, User will be rolled back (no orphans)
        return DB::transaction(function () use ($data) {
            
            // STEP 1: Extract user data from form
            // WHY: Form has nested structure: $data['user']['name']
            // We need to separate user data from tenant data
            $userData = $data['user'];
            unset($data['user']); // Remove user data from tenant array
            
            // STEP 2: Hash password (security)
            // WHY: Never store plain text passwords in database
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }
            
            // STEP 3: Create User first
            // WHY: We need user.id to link tenant
            $user = \App\Models\User::create($userData);
            
            // STEP 4: Link tenant to user
            // WHY: Tenant table has user_id foreign key
            $data['user_id'] = $user->id;
            
            // STEP 5: Ensure tenant belongs to same company as user
            // WHY: Multi-tenancy - tenant must be in same company as user
            // This prevents company A admin from creating tenant in company B
            $data['company_id'] = $user->company_id;
            
            // STEP 6: Create Tenant
            return static::getModel()::create($data);
        });
    }

    // 🔥 PERFORMANCE: No page reload
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // 🔥 UX: Success message
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Tenant created successfully';
    }
}