<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'student' => \App\Http\Middleware\EnsureUserIsStudent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            return redirect()->route('home')->with('status', '页面不存在。');
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        });

        $exceptions->render(function (\RuntimeException $e, $request) {
            report($e);
            $message = config('app.debug') ? $e->getMessage() : '操作失败，请稍后重试。';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 500);
            }
            return back()->withErrors(['error' => $message]);
        });
    })->create();
