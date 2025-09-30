<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Append CORS and global API throttle without removing Laravel's defaults
        $middleware->appendToGroup('api', \Illuminate\Http\Middleware\HandleCors::class);
        $middleware->appendToGroup('api', \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (str_starts_with($request->path(), 'api/')) {
                $status = 500;
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'message' => 'Validation failed',
                            'details' => $e->errors(),
                        ],
                    ], 422);
                }
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json(['success' => false, 'error' => ['message' => 'Unauthorized']], 401);
                }
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json(['success' => false, 'error' => ['message' => 'Forbidden']], 403);
                }
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json(['success' => false, 'error' => ['message' => 'Not found']], 404);
                }
                $payload = ['success' => false, 'error' => ['message' => 'Server error']];
                if (config('app.debug')) {
                    $payload['error']['exception'] = class_basename($e);
                    $payload['error']['message'] = $e->getMessage();
                }
                return response()->json($payload, $status);
            }
        });
    })->create();
