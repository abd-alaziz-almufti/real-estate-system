<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'super@admin.com')->first();
if ($user) {
    echo "Found user ID: " . $user->id . "\n";
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    if (!$user->hasRole('super_admin')) {
        $user->assignRole('super_admin');
        echo "Role super_admin assigned.\n";
    } else {
        echo "User already has super_admin role.\n";
    }
    $user->load('roles');
    echo "Current roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
} else {
    echo "User super@admin.com not found.\n";
}
