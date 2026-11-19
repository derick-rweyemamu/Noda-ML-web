<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'groups.view',
            'groups.create',
            'groups.assign_officer',
            'contacts.view',
            'members.add',
            'members.edit',
            'loans.view',
            'loans.create',
            'payments.view',
            'payments.record',
            'mpesa.simulate',
            'penalties.sweep',
            'penalties.apply',
            'notifications.view',
            'notifications.dispatch',
            'templates.view',
            'templates.manage',
            'reports.view',
            'reports.manage',
            'settings.view',
            'settings.update',
            'staff.create',
            'staff.edit',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'member.portal.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions($permissions);

        $director = Role::firstOrCreate(['name' => 'Director']);
        $director->syncPermissions([
            'groups.view', 'groups.assign_officer', 'contacts.view', 'loans.view', 'payments.view',
            'penalties.sweep', 'penalties.apply',
            'notifications.view', 'notifications.dispatch',
            'templates.view', 'templates.manage',
            'reports.view', 'reports.manage',
            'settings.view', 'roles.view',
        ]);

        $loanOfficer = Role::firstOrCreate(['name' => 'Loan Officer']);
        $loanOfficer->syncPermissions([
            'groups.view', 'groups.create', 'contacts.view',
            'members.add', 'members.edit',
            'loans.view', 'loans.create',
            'payments.view', 'notifications.view', 'templates.view', 'reports.view',
        ]);

        $cashier = Role::firstOrCreate(['name' => 'Cashier']);
        $cashier->syncPermissions([
            'groups.view', 'contacts.view', 'loans.view',
            'payments.view', 'payments.record',
            'mpesa.simulate', 'penalties.sweep',
            'notifications.view', 'templates.view', 'reports.view',
        ]);

        $member = Role::firstOrCreate(['name' => 'Member']);
        $member->syncPermissions(['member.portal.view']);
    }
}
