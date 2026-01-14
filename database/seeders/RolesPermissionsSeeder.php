<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perms = [
            'view dashboard',
            'manage users',
            'view reports',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $staff = Role::firstOrCreate(['name' => 'staff']);

        $admin->givePermissionTo($perms);
        $staff->givePermissionTo(['view dashboard', 'view reports']);

        // Verify admin permissions
        $adminPermissions = $admin->permissions->pluck('name');
        echo 'Admin permissions: '.$adminPermissions->join(', ')."\n";
    }
}
