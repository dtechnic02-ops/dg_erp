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

    ->withMiddleware(function (Middleware $middleware): void {

        // 🔥 ROLE MIDDLEWARE
        $middleware->alias([

            'subscription' =>
                \App\Http\Middleware\CheckSubscription::class,

            'subscription.module' =>
                \App\Http\Middleware\EnsureSubscriptionModule::class,

            'permission' =>
                \App\Http\Middleware\EnsurePermission::class,

            'platform.user' =>
                \App\Http\Middleware\EnsurePlatformUser::class,

            'platform.permission' =>
                \App\Http\Middleware\EnsurePlatformPermission::class,

            'company.user' =>
                \App\Http\Middleware\EnsureCompanyUser::class,

        ]);

        // 🔥 ONLINE / OFFLINE
        $middleware->append(

            \App\Http\Middleware\UpdateLastSeen::class

        );

    })

    ->withExceptions(function (Exceptions $exceptions): void {

        //
    })

    ->create();
