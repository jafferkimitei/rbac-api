<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacSeeder extends Seeder
{
    public function run(): void
    {

        $guard = config('auth.defaults.guard', 'web');

        $permissions = [
            'view users',
            'manage users',
            'view reports',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate([
                'name' => $p,
                'guard_name' => $guard,
            ]);
        }

        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => $guard,
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => $guard,
        ]);

        $superAdminRole->syncPermissions(Permission::where('guard_name', $guard)->get());

        $adminRole->syncPermissions([
            Permission::where('name', 'view users')->where('guard_name', $guard)->first(),
            Permission::where('name', 'view reports')->where('guard_name', $guard)->first(),
        ]);

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Password123!'),
            ]
        );
        $superAdmin->syncRoles([$superAdminRole]);

        $adminLimited = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Limited',
                'password' => Hash::make('Password123!'),
            ]
        );
        $adminLimited->syncRoles([$adminRole]);
    }
}
