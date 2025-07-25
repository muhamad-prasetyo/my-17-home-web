<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\OptimizePerformance;
<<<<<<< HEAD
=======
use App\Http\Middleware\VerifyCsrfToken;
>>>>>>> 6ad12440d924f1a0aa1d26348cd63a38329565ff

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Apply OptimizePerformance middleware to all API routes
        $middleware->api(OptimizePerformance::class);
<<<<<<< HEAD
=======
        
        // Override default VerifyCsrfToken with our custom implementation
        $middleware->replace('csrf', VerifyCsrfToken::class);
>>>>>>> 6ad12440d924f1a0aa1d26348cd63a38329565ff
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
