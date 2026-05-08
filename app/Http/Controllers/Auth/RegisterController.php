<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function create()
    {
        if (! Setting::get('registration_enabled', false)) {
            abort(404);
        }

        return view('auth.register');
    }

    public function store(RegisterRequest $request)
    {
        if (! Setting::get('registration_enabled', false)) {
            abort(404);
        }

        $validated = $request->validated();

        $requiresApproval = Setting::get('registration_requires_approval', false);

        $user = User::create([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_STUDENT,
            'approval_status' => $requiresApproval ? User::APPROVAL_PENDING : User::APPROVAL_APPROVED,
            'approved_at' => $requiresApproval ? null : now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        if ($requiresApproval) {
            return redirect()->route('pending.approval');
        }

        return redirect()->route('student.dashboard');
    }
}
