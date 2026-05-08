<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;

class PendingApprovalController extends Controller
{
    public function pending()
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user || $user->role !== User::ROLE_STUDENT) {
            return redirect()->route('login');
        }

        if ($user->approval_status !== User::APPROVAL_PENDING) {
            return redirect()->route('student.dashboard');
        }

        return view('student.pending');
    }

    public function rejected()
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user || $user->role !== User::ROLE_STUDENT) {
            return redirect()->route('login');
        }

        if ($user->approval_status !== User::APPROVAL_REJECTED) {
            return redirect()->route('student.dashboard');
        }

        return view('student.rejected');
    }
}
