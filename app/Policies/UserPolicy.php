<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->canManageUsers();
    }

    public function view(User $actor, User $user): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->canManageUser($user);
    }

    public function create(User $actor): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->canManageUsers();
    }

    public function update(User $actor, User $user): bool
    {
        if ($actor->id === $user->id) {
            return $actor->isSuperAdmin();
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->canManageUser($user);
    }

    public function delete(User $actor, User $user): bool
    {
        if ($actor->id === $user->id) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->canManageUser($user);
    }

    public function approve(User $actor, User $user): bool
    {
        if ($user->role !== User::ROLE_STUDENT) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->canManageUser($user);
    }

    public function reject(User $actor, User $user): bool
    {
        return $this->approve($actor, $user);
    }
}
