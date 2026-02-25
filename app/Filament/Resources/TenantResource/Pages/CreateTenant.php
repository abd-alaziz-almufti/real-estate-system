<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
    // app/Filament/Resources/TenantResource/Pages/CreateTenant.php

protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
{
    // 1. Extract user data
    $userData = $data['user'];
    unset($data['user']); // Remove user data from tenant array

    // 2. Hash password
    if (isset($userData['password'])) {
        $userData['password'] = \Illuminate\Support\Facades\Hash::make($userData['password']);
    }

    // 3. Create User
    $user = \App\Models\User::create($userData);

    // 4. Create Tenant and link to user
    $data['user_id'] = $user->id;
    // Ensure tenant also belongs to the same company
    $data['company_id'] = $user->company_id; 

    return static::getModel()::create($data);
}
}
