<?php

use App\Http\Middleware\EnsureInformationIdentity;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\Government\EnsureGovernmentAdministrator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/information.php',
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/government.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'government.admin' => EnsureGovernmentAdministrator::class,
            'information.identity' => EnsureInformationIdentity::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('gov', 'gov/*')
            ? route('government.login')
            : route('login'));
        $middleware->redirectUsersTo(fn (Request $request): string => $request->is('gov', 'gov/*')
            ? route('government.dashboard')
            : route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
