<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin' => [
                'display_name' => 'Super Admin',
                'description' => 'Full system access',
                'permissions' => [] // Handled by isSuperAdmin bypass
            ],
            'admin' => [
                'display_name' => 'Administrator',
                'description' => 'Most managerial functions',
                'permissions' => ['users.view', 'roles.view', 'leads.view', 'enrollments.view', 'payments.view', 'expenses.view', 'reports.view', 'leads.bulk_email', 'leads.bulk_email_history', 'payroll.manage', 'payroll.view', 'payroll.generate', 'payroll.approve', 'attendance.view', 'attendance.mark']
            ],
            'bde' => [
                'display_name' => 'Business Development Executive',
                'description' => 'Lead management and sales',
                'permissions' => ['leads.view_assigned', 'leads.create', 'leads.update', 'enrollments.create', 'leads.bulk_email', 'leads.bulk_email_history']
            ],
            'accounts' => [
                'display_name' => 'Accounts Manager',
                'description' => 'Finances and payments',
                'permissions' => ['payments.view', 'payments.create', 'expenses.view', 'expenses.create', 'reports.view']
            ],
        ];

        foreach ($roles as $name => $data) {
            $rolePermissions = $data['permissions'];
            unset($data['permissions']);

            $role = Role::updateOrCreate(['name' => $name], array_merge($data, ['is_system' => true]));

            if (!empty($rolePermissions)) {
                $ids = Permission::whereIn('name', $rolePermissions)->pluck('id');
                $role->permissions()->sync($ids);
            }
        }
    }
}
