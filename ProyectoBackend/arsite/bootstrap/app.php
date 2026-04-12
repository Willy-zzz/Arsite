<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Auth\Access\AuthorizationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //Grupo WEB
        //$middleware->web(append: [
          //  \App\Http\Middleware\EncryptCookies::class,
            //\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            //\Illuminate\Session\Middleware\StartSession::class,
            //\Illuminate\View\Middleware\ShareErrorsFromSession::class,
            //\App\Http\Middleware\VerifyCsrfToken::class,
            //\Illuminate\Routing\Middleware\SubstituteBindings::class,

        //]);

        //Grupo API
        $middleware->api(prepend: [
            //Que se ejecute CORS antes que nada
            \Illuminate\Http\Middleware\HandleCors::class,
            //Necesario para que Sanctum pueda manejar la autenticación
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            //CORS
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);
        
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //Manejar excepciones de autorización (policies)
        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción.',
                    'error' => $e->getMessage()
                ], 403);
            }
        });
    })->create();
