<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ExamPaper;
use App\Models\PracticeAttempt;
use App\Models\Question;
use App\Models\Setting;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\ExamPaperPolicy;
use App\Policies\PracticeAttemptPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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

        Gate::policy(User::class, UserPolicy::class)
            ->policy(Question::class, QuestionPolicy::class)
            ->policy(Category::class, CategoryPolicy::class)
            ->policy(ExamPaper::class, ExamPaperPolicy::class)
            ->policy(PracticeAttempt::class, PracticeAttemptPolicy::class);

        RedirectIfAuthenticated::redirectUsing(function () {
            $user = auth()->user();

            if (! $user) {
                return route('home');
            }

            return $user->isAdmin()
                ? route('admin.dashboard')
                : route('student.dashboard');
        });

        View::composer(['layouts.app', 'student.dashboard', 'admin.dashboard'], function ($view) {
            $settings = Cache::remember('app_settings', 3600, function () {
                return [
                    'registration_enabled' => Setting::get('registration_enabled', false),
                    'registration_requires_approval' => Setting::get('registration_requires_approval', false),
                    'questions_per_session' => (int) Setting::get('questions_per_session', config('practice.questions_per_session')),
                ];
            });
            $view->with('settings', $settings);
        });
    }
}
