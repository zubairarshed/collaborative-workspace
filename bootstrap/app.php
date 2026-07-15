<?php

use App\Exceptions\StaleEntityException;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withEvents()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // OCC conflicts (ADR-004) always render as a JSON 409, even for
        // Inertia visits: board mutations go through the Inertia router,
        // which treats a non-Inertia response as an "httpException" event.
        // resources/js/lib/staleEntity.ts intercepts status 409 there,
        // toasts, and partially reloads the board props — so the body only
        // needs the id/version the client reconciles against; the fresh
        // board state arrives via the partial reload.
        $exceptions->render(fn (StaleEntityException $e, Request $request) => response()->json([
            'message' => __('This item was updated by someone else.'),
            'entity' => $e->freshState(),
        ], 409));
    })->create();
