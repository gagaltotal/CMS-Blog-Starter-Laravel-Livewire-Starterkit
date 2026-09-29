<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    private const GUARD = 'web';

    /**
     * Creates or updates the seeded admin account.
     *
     * Password behavior:
     *
     * - ADMIN_SEED_PASSWORD configured:
     *     use configured password
     *
     * - APP_ENV=local:
     *     use documented local password
     *
     * - Other environments:
     *     generate random password and print it once
     */
    public function run(): void
    {
        $email = (string) config('cms.admin_seed_email');
        $configuredPassword = config('cms.admin_seed_password');

        if (blank($email)) {
            throw new \RuntimeException(
                'Admin seed email is not configured. Check CMS_ADMIN_SEED_EMAIL / config(cms.admin_seed_email).'
            );
        }

        $generatedPassword = null;

        if (filled($configuredPassword)) {
            $password = (string) $configuredPassword;
        } elseif (app()->environment('local')) {
            $password = 'AdminPass123!';
        } else {
            $password = $generatedPassword = Str::password(20);
        }

        /*
         * ============================================================
         * 1. CREATE / UPDATE ADMIN USER
         * ============================================================
         *
         * Password is assigned as plain text because the User model
         * uses Laravel's 'hashed' cast.
         */
        $admin = User::query()->firstOrNew([
            'email' => $email,
        ]);

        $admin->forceFill([
            'name' => 'Admin',
            'password' => $password,
            'email_verified_at' => now(),
            'is_active' => true,
        ])->save();

        /*
         * ============================================================
         * 2. GET ADMIN ROLE
         * ============================================================
         *
         * RolesAndPermissionsSeeder runs BEFORE this seeder.
         */
        $adminRole = Role::query()
            ->where('name', 'admin')
            ->where('guard_name', self::GUARD)
            ->first();

        if (! $adminRole) {
            throw new \RuntimeException(
                'Admin role does not exist. Run RolesAndPermissionsSeeder first.'
            );
        }

        /*
         * ============================================================
         * 3. ASSIGN ADMIN ROLE TO USER
         * ============================================================
         *
         * This creates the model_has_roles row.
         */
        $admin->syncRoles([$adminRole]);

        /*
         * Clear cache so the current user immediately gets the
         * correct role/permission state.
         */
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        $this->command?->info(
            "Admin user ready: {$email}"
        );

        $this->command?->info(
            'Role assigned: admin'
        );

        if ($generatedPassword) {
            $this->command?->warn(
                "Generated admin password (shown once): {$generatedPassword}"
            );
        } elseif (app()->environment('local')) {
            $this->command?->info(
                'Password: AdminPass123! (local only — change this before deploying anywhere.)'
            );
        }
    }
}