<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

    //     User::factory()->create([
    //         'name' => 'Test User',
    //         'email' => 'test@example.com',
    //     ]);
     $palestine = Location::create([
            'name' => 'Palestine',
            'type' => 'country',
            'parent_id' => null,
        ]);

        $nablus = Location::create([
            'name' => 'Nablus',
            'type' => 'city',
            'parent_id' => $palestine->id,
            'latitude' => 32.2211,
            'longitude' => 35.2544,
        ]);

        $oldCity = Location::create([
            'name' => 'Old City',
            'type' => 'district',
            'parent_id' => $nablus->id,
        ]);

        // Create company
        $company = Company::create([
            'name' => 'Real Estate Co.',
            'email' => 'info@realestatecompany.ps',
            'phone' => '+970599123456',
            'address' => 'Nablus, Palestine',
            'is_active' => true,
        ]);

        // Create users
        User::create([
            'company_id' => $company->id,
            'name' => 'Admin User',
            'email' => 'admin@realestatecompany.ps',
            'password' => bcrypt('password'),
            'role' => 'company_admin',
            'phone' => '+970599111111',
        ]);

        User::create([
            'company_id' => $company->id,
            'name' => 'Property Manager',
            'email' => 'manager@realestatecompany.ps',
            'password' => bcrypt('password'),
            'role' => 'property_manager',
            'phone' => '+970599222222',
        ]);
    }
}
