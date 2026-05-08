<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, User $user): bool
    {
        return $this->canModifyUser($actor, $user);
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, User $user): bool
    {
        return $this->canModifyUser($actor, $user);
    }

    public function delete(User $actor, User $user): bool
    {
        return $this->canModifyUser($actor, $user) && $actor->id !== $user->id;
    }

    public function approve(User $actor, User $user): bool
    {
        return $user->role === User::ROLE_STUDENT && $actor->isAdmin();
    }

    public function reject(User $actor, User $user): bool
    {
        return $this->approve($actor, $user);
    }

    private function canModifyUser(User $actor, User $target): bool
    {
        if ($target->isSuperAdmin()) {
            return $actor->isSuperAdmin();
        }

        if ($target->role === User::ROLE_ADMIN) {
            return $actor->isSuperAdmin();
        }

        return $actor->isAdmin();
    }
}
