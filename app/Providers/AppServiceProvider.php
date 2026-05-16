<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination::bootstrap-5');

        Gate::policy(User::class, UserPolicy::class);

        RedirectIfAuthenticated::redirectUsing(function () {
            $user = auth()->user();

            if (! $user) {
                return route('home');
            }

            return $user->isAdmin()
                ? route('admin.dashboard')
                : route('student.dashboard');
        });
    }
}
