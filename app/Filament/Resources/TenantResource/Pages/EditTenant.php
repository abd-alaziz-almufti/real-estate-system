<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    // app/Filament/Resources/TenantResource/Pages/EditTenant.php

protected function mutateFormDataBeforeFill(array $data): array
{
    // Load existing user data into the form
    if ($this->record->user) {
        $data['user'] = $this->record->user->toArray();
    }
    return $data;
}

protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
{
    // 1. Extract and Update User
    if (isset($data['user'])) {
        $userData = $data['user'];
        
        // Hash password if changed
        if (filled($userData['password'] ?? null)) {
            $userData['password'] = \Illuminate\Support\Facades\Hash::make($userData['password']);
        } else {
            unset($userData['password']);
        }

        $record->user->update($userData);
        unset($data['user']);
    }

    // 2. Update Tenant
    $record->update($data);

    return $record;
}
}
