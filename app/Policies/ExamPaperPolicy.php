<?php

namespace App\Policies;

use App\Models\ExamPaper;
use App\Models\User;

class ExamPaperPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, ExamPaper $paper): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, ExamPaper $paper): bool
    {
        return $user->isSuperAdmin();
    }
}
