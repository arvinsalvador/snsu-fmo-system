<?php

use App\Http\Middleware\EnsureApiIdempotency;
use App\Http\Middleware\NormalizeApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['idempotency' => EnsureApiIdempotency::class]);
        $middleware->appendToGroup('api', NormalizeApiResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $api = fn (Request $request): bool => $request->is('api/*');
        $exceptions->render(fn (ValidationException $e, Request $r) => $api($r) ? response()->json(['success' => false, 'message' => 'The submitted data is invalid.', 'errors' => $e->errors(), 'error_code' => 'VALIDATION_ERROR'], 422) : null);
        $exceptions->render(fn (AuthenticationException $e, Request $r) => $api($r) ? response()->json(['success' => false, 'message' => 'Authentication is required.', 'errors' => (object) [], 'error_code' => 'UNAUTHENTICATED'], 401) : null);
        $exceptions->render(fn (AuthorizationException $e, Request $r) => $api($r) ? response()->json(['success' => false, 'message' => 'You are not authorized to perform this action.', 'errors' => (object) [], 'error_code' => 'FORBIDDEN'], 403) : null);
        $exceptions->render(fn (ModelNotFoundException|NotFoundHttpException $e, Request $r) => $api($r) ? response()->json(['success' => false, 'message' => 'The requested resource was not found.', 'errors' => (object) [], 'error_code' => 'NOT_FOUND'], 404) : null);
        $exceptions->render(fn (TooManyRequestsHttpException $e, Request $r) => $api($r) ? response()->json(['success' => false, 'message' => 'Too many requests. Please try again later.', 'errors' => (object) [], 'error_code' => 'RATE_LIMITED'], 429) : null);
        $exceptions->render(function (HttpExceptionInterface $e, Request $r) use ($api) {
            if (! $api($r)) {
                return null;
            }$status = $e->getStatusCode();
            $code = match ($status) {
                403 => 'FORBIDDEN',404 => 'NOT_FOUND',409 => 'CONFLICT',422 => 'VALIDATION_ERROR',429 => 'RATE_LIMITED',default => 'REQUEST_FAILED'
            };

            return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'The request could not be completed.', 'errors' => (object) [], 'error_code' => $code], $status);
        });
        $exceptions->render(fn (UniqueConstraintViolationException $e, Request $r) => $api($r) ? response()->json(['success' => false, 'message' => 'The request conflicts with an existing record.', 'errors' => (object) [], 'error_code' => 'CONFLICT'], 409) : null);
        $exceptions->render(fn (Throwable $e, Request $r) => $api($r) ? response()->json(['success' => false, 'message' => 'The request could not be completed.', 'errors' => (object) [], 'error_code' => 'SERVER_ERROR'], 500) : null);
    })->create();
