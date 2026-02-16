<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Project permissions
            'view projects',
            'create projects',
            'update projects',
            'delete projects',
            

            // Task permissions
            'view tasks',
            'create tasks',
            'update tasks',
            'delete tasks',
            'assign tasks',

            // User management
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles and assign permissions
        Role::create(['name' => 'member'])
            ->givePermissionTo([
                'view projects',
                'view tasks',
                'create tasks',
                'update tasks',
            ]);

        Role::create(['name' => 'manager'])
            ->givePermissionTo([
                'view projects',
                'create projects',
                'update projects',
                'view tasks',
                'create tasks',
                'update tasks',
                'delete tasks',
                'assign tasks',
            ]);

        Role::create(['name' => 'admin'])
            ->givePermissionTo(Permission::all());
    }
}
