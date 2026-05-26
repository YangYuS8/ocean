<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\AuthenticateOceanToken;
use App\Http\Middleware\EnsureOceanPermission;
use App\Http\Middleware\InjectOceanActor;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            InjectOceanActor::class,
        ]);

        $middleware->alias([
            'ocean.auth' => AuthenticateOceanToken::class,
            'ocean.permission' => EnsureOceanPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ApiException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => [
                        'code' => $exception->errorCode(),
                        'message' => $exception->getMessage(),
                    ],
                ], $exception->statusCode());
            }

            return null;
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'resource not found',
                    ],
                ], 404);
            }

            return null;
        });
    })->create();
