<?php

namespace Database\Seeders;

use App\Authorization\Permissions;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::all() as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        $superAdmin = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web']
        );
        $superAdmin->syncPermissions(Permission::query()->where('guard_name', 'web')->pluck('name')->all());

        $staff = Role::firstOrCreate(
            ['name' => 'Staff', 'guard_name' => 'web']
        );
        $staff->syncPermissions([
            Permissions::DASHBOARD_ACCESS,
            Permissions::BENEFICIARIES_VIEW,
            Permissions::BENEFICIARIES_MANAGE,
            Permissions::PROJECTS_VIEW,
            Permissions::PROJECTS_MANAGE,
            Permissions::TRAININGS_VIEW,
            Permissions::TRAININGS_MANAGE,
            Permissions::ASSISTANCE_VIEW,
            Permissions::ASSISTANCE_MANAGE,
            Permissions::GROUPS_VIEW,
            Permissions::GROUPS_MANAGE,
            Permissions::REPORTS_EXPORT,
            Permissions::NOTIFICATIONS_ACCESS,
        ]);

        $viewer = Role::firstOrCreate(
            ['name' => 'Viewer', 'guard_name' => 'web']
        );
        $viewer->syncPermissions([
            Permissions::DASHBOARD_ACCESS,
            Permissions::BENEFICIARIES_VIEW,
            Permissions::PROJECTS_VIEW,
            Permissions::TRAININGS_VIEW,
            Permissions::ASSISTANCE_VIEW,
            Permissions::GROUPS_VIEW,
            Permissions::NOTIFICATIONS_ACCESS,
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
