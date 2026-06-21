<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsStudent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'student' => EnsureUserIsStudent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            return redirect()->route('home')->with('status', '页面不存在。');
        });

        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        });

        $exceptions->render(function (RuntimeException $e, $request) {
            report($e);
            $message = config('app.debug') ? $e->getMessage() : '操作失败，请稍后重试。';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 500);
            }

            return back()->withErrors(['error' => $message]);
        });
    })->create();
