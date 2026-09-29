<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage users');
    }

    public function create(User $user): bool
    {
        return $user->can('manage users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('manage users');
    }

    /**
     * Two specific self-protection rules on top of the base permission:
     * nobody can delete their own account from this screen (avoids an
     * accidental self-lockout), and the very last account holding the
     * admin role can't be deleted or demoted, which would otherwise leave
     * the whole CMS with no one able to manage it.
     */
    public function delete(User $user, User $model): bool
    {
        if (! $user->can('manage users')) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        if ($model->hasRole('admin') && User::role('admin')->count() <= 1) {
            return false;
        }

        return true;
    }

    public function changeRole(User $user, User $model): bool
    {
        if (! $user->can('manage users')) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }
}
