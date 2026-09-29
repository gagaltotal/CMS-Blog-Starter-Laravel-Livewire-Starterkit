<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    private const GUARD = 'web';

    /**
     * Full CMS permission set.
     *
     * @var list<string>
     */
    private array $permissions = [
        'view dashboard',
        'manage own posts',
        'manage all posts',
        'publish posts',
        'manage categories',
        'manage users',
        'manage settings',
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        
        $registrar->forgetCachedPermissions();

        /*
         * ============================================================
         * 1. CREATE ALL PERMISSIONS FIRST
         * ============================================================
         *
         * This was the missing part in your previous seeder.
         */
        $permissions = [];

        foreach ($this->permissions as $permissionName) {
            $permissions[$permissionName] = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => self::GUARD,
            ]);
        }

        /*
         * ============================================================
         * 2. ADMIN
         * ============================================================
         */
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => self::GUARD,
        ]);

        $admin->syncPermissions(
            array_values($permissions)
        );

        /*
         * ============================================================
         * 3. EDITOR
         * ============================================================
         */
        $editor = Role::firstOrCreate([
            'name' => 'editor',
            'guard_name' => self::GUARD,
        ]);

        $editor->syncPermissions([
            $permissions['view dashboard'],
            $permissions['manage all posts'],
            $permissions['publish posts'],
            $permissions['manage categories'],
        ]);

        /*
         * ============================================================
         * 4. AUTHOR
         * ============================================================
         */
        $author = Role::firstOrCreate([
            'name' => 'author',
            'guard_name' => self::GUARD,
        ]);

        $author->syncPermissions([
            $permissions['view dashboard'],
            $permissions['manage own posts'],
        ]);

        $registrar->forgetCachedPermissions();

        $this->command?->info(
            'Roles & permissions seeded successfully: admin, editor, author.'
        );

        $this->command?->info(
            'Permissions created: ' . count($permissions)
        );
    }
}