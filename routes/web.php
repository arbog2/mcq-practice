<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EditorUploadController as AdminEditorUploadController;
use App\Http\Controllers\Admin\OrganizationUnitController as AdminOrganizationUnitController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\StatsController as AdminStatsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\PendingApprovalController;
use App\Http\Controllers\Student\PracticeController;
use App\Http\Controllers\Student\WrongBookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    /** @var \App\Models\User $user */
    $user = auth()->user();

    return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'student.dashboard');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    if (config('practice.registration_enabled')) {
        Route::get('register', [RegisterController::class, 'create'])->name('register');
        Route::post('register', [RegisterController::class, 'store']);
    }
});

Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('guest')->group(function () {
    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/pending-approval', [PendingApprovalController::class, 'pending'])->name('pending.approval');
    Route::get('/account-rejected', [PendingApprovalController::class, 'rejected'])->name('rejected.approval');
});

Route::middleware(['auth', 'student'])->group(function () {
    Route::get('/student', StudentDashboardController::class)->name('student.dashboard');

    Route::get('/practice/categories', [PracticeController::class, 'categories'])->name('student.categories');
    Route::post('/practice/categories/{category}/start', [PracticeController::class, 'start'])->name('student.categories.start');

    Route::get('/practice/attempts/{attempt}', [PracticeController::class, 'showAttempt'])->name('student.attempts.show');
    Route::post('/practice/attempts/{attempt}/submit', [PracticeController::class, 'submit'])->name('student.attempts.submit');
    Route::get('/practice/attempts/{attempt}/result', [PracticeController::class, 'result'])->name('student.attempts.result');
    Route::get('/practice/history', [PracticeController::class, 'history'])->name('student.attempts.history');

    Route::get('/wrong-book', [WrongBookController::class, 'index'])->name('student.wrong-book');
    Route::get('/wrong-book/review', [WrongBookController::class, 'reviewForm'])->name('student.wrong-book.review');
    Route::post('/wrong-book/review/start', [WrongBookController::class, 'startReview'])->name('student.wrong-book.review.start');
    Route::post('/wrong-book/{userWrongQuestion}/master', [WrongBookController::class, 'master'])->name('student.wrong-book.master');

    Route::get('/profile', [StudentProfileController::class, 'edit'])->name('student.profile.edit');
    Route::put('/profile', [StudentProfileController::class, 'update'])->name('student.profile.update');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');

    Route::get('/stats/wrong-by-category', [AdminStatsController::class, 'wrongByCategory'])->name('stats.wrong-by-category');
    Route::get('/stats/wrong-by-category/export', [AdminStatsController::class, 'exportWrongByCategory'])->name('stats.wrong-by-category.export');

    Route::get('/users-import/template', [AdminUserController::class, 'importTemplate'])->name('users.import.template');
    Route::get('/users-import', [AdminUserController::class, 'importForm'])->name('users.import');
    Route::post('/users-import', [AdminUserController::class, 'importStore'])->name('users.import.store');

    Route::resource('users', AdminUserController::class)->except(['show']);
    Route::post('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
    Route::post('/users/{user}/reject', [AdminUserController::class, 'reject'])->name('users.reject');

    Route::post('/editor/upload-image', [AdminEditorUploadController::class, 'store'])->name('editor.upload-image');

    Route::get('/organization-units', [AdminOrganizationUnitController::class, 'index'])->name('organization-units.index');
    Route::post('/organization-units', [AdminOrganizationUnitController::class, 'store'])->name('organization-units.store');
    Route::delete('/organization-units/{organization_unit}', [AdminOrganizationUnitController::class, 'destroy'])->name('organization-units.destroy');

    Route::resource('categories', AdminCategoryController::class)->except(['show']);

    Route::get('/questions-import/template', [AdminQuestionController::class, 'importTemplate'])->name('questions.import.template');
    Route::get('/questions-import', [AdminQuestionController::class, 'importForm'])->name('questions.import');
    Route::post('/questions-import', [AdminQuestionController::class, 'importStore'])->name('questions.import.store');

    Route::post('/questions/batch-move', [AdminQuestionController::class, 'batchMoveCategory'])->name('questions.batch-move');
    Route::get('/questions/{question}/move', [AdminQuestionController::class, 'moveForm'])->name('questions.move.form');
    Route::post('/questions/{question}/move', [AdminQuestionController::class, 'moveCategory'])->name('questions.move');
    Route::resource('questions', AdminQuestionController::class)->except(['show']);
});
