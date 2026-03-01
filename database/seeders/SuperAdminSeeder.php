<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
        ['email' => 'admin@elements.com'],
        [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'employee_id' => 'EMP001',
            'status' => 'active',
        ]
        );

        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $admin->roles()->sync([$superAdminRole->id]);
        }
    }
}
