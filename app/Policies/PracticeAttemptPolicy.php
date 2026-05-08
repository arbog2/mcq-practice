<?php

namespace App\Policies;

use App\Models\PracticeAttempt;
use App\Models\User;

class PracticeAttemptPolicy
{
    public function view(User $user, PracticeAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id;
    }

    public function submit(User $user, PracticeAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id && $attempt->status === PracticeAttempt::STATUS_IN_PROGRESS;
    }

    public function result(User $user, PracticeAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id && $attempt->status === PracticeAttempt::STATUS_SUBMITTED;
    }
}
