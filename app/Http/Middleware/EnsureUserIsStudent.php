<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== User::ROLE_STUDENT) {
            abort(403);
        }

        if ($user->approval_status === User::APPROVAL_PENDING) {
            return redirect()->route('pending.approval');
        }

        if ($user->approval_status === User::APPROVAL_REJECTED) {
            return redirect()->route('rejected.approval');
        }

        return $next($request);
    }
}
