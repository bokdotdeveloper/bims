<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@slp-bims.gov.ph'],
            [
                'name' => 'SLP Administrator',
                'password' => Hash::make('Admin@1234'),
            ]
        );
        $admin->syncRoles(['Super Admin']);

        $staff1 = User::firstOrCreate(
            ['email' => 'staff1@slp-bims.gov.ph'],
            [
                'name' => 'Maria Santos',
                'password' => Hash::make('Staff@1234'),
            ]
        );
        $staff1->syncRoles(['Staff']);

        $staff2 = User::firstOrCreate(
            ['email' => 'staff2@slp-bims.gov.ph'],
            [
                'name' => 'Juan dela Cruz',
                'password' => Hash::make('Staff@1234'),
            ]
        );
        $staff2->syncRoles(['Staff']);
    }
}
