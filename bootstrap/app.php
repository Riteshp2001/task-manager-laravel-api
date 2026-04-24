<?php

use App\Http\Middleware\EnsureRole;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Validation failed.', $exception->errors(), 422);
            }
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Authentication is required.', null, 401);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error($exception->getMessage() ?: 'You are not allowed to perform this action.', null, 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Resource not found.', null, 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Endpoint not found.', null, 404);
            }
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if ($request->expectsJson()) {
                $message = config('app.debug')
                    ? $exception->getMessage()
                    : 'Something went wrong. Please try again.';

                return ApiResponse::error($message, null, 500);
            }
        });
    })->create();
