<?php
// app/Filament/Resources/TenantResource/Pages/EditTenant.php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    // 🔥 Load user data into form for editing
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // ✅ WHY: Form expects nested structure: $data['user']['name']
        // Load user relationship data
        $data['user'] = [
            'name' => $this->record->user->name,
            'email' => $this->record->user->email,
            'phone' => $this->record->user->phone,
            'company_id' => $this->record->user->company_id,
        ];

        return $data;
    }

    // 🔥 Update both User and Tenant tables
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            
            // STEP 1: Update user if data provided
            if (isset($data['user'])) {
                $userData = [
                    'name' => $data['user']['name'],
                    'email' => $data['user']['email'],
                    'phone' => $data['user']['phone'] ?? null,
                ];

                // ✅ Only update password if provided (not empty)
                if (!empty($data['user']['password'])) {
                    $userData['password'] = Hash::make($data['user']['password']);
                }

                // ✅ Update company_id (super admin can change it)
                if (isset($data['user']['company_id'])) {
                    $userData['company_id'] = $data['user']['company_id'];
                    
                    // ✅ IMPORTANT: Update tenant's company_id too (keep in sync)
                    $data['company_id'] = $data['user']['company_id'];
                }

                // Update user
                $record->user->update($userData);
                
                // Remove user data from tenant data
                unset($data['user']);
            }

            // STEP 2: Update tenant
            $record->update($data);

            return $record;
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Tenant updated successfully';
    }
}

