<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view dashboard');
    }

    public function view(User $user, Post $post): bool
    {
        return $this->ownsOrManagesAll($user, $post);
    }

    public function create(User $user): bool
    {
        return $user->can('manage all posts') || $user->can('manage own posts');
    }

    public function update(User $user, Post $post): bool
    {
        return $this->ownsOrManagesAll($user, $post);
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->ownsOrManagesAll($user, $post);
    }

    /**
     * Separate from update() on purpose: an Author can be allowed to write
     * and edit their own posts without being allowed to make them public,
     * which is a common editorial workflow (draft -> review -> publish).
     */
    public function publish(User $user, Post $post): bool
    {
        return $user->can('publish posts');
    }

    private function ownsOrManagesAll(User $user, Post $post): bool
    {
        if ($user->can('manage all posts')) {
            return true;
        }

        return $user->can('manage own posts') && $post->user_id === $user->id;
    }
}
