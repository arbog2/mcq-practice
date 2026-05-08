<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => __('登录信息不正确'),
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->role === User::ROLE_STUDENT) {
            if ($user->approval_status === User::APPROVAL_PENDING) {
                return redirect()->intended(route('pending.approval'));
            }

            if ($user->approval_status === User::APPROVAL_REJECTED) {
                Auth::logout();

                throw ValidationException::withMessages([
                    'username' => __('账号审核未通过'),
                ]);
            }
        }

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('student.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
