<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render the real layouts, which call @vite(); there
        // is no built manifest in a test run, so stub it out.
        $this->withoutVite();
    }

    /**
     * Creates an active, verified user holding the given role
     * ('admin' | 'editor' | 'author'), seeding roles & permissions first.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function userWithRole(string $role, array $attributes = []): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}
