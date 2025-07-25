<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class OptimizePerformance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Increase memory limit for API requests
        ini_set('memory_limit', '512M');
        
        // Increase maximum execution time for API requests to 60 seconds
        // This helps with complex queries and operations
        ini_set('max_execution_time', 60);
        
        // Disable unnecessary features that can slow down the application
        if (!config('app.debug')) {
            // Disable error reporting in production for performance
            error_reporting(0);
            
            // Disable automatic database query logging
            \DB::disableQueryLog();
        }
        
        $startTime = microtime(true);
        
        // Process the request
        $response = $next($request);
        
        // Log execution time for slow requests (more than 1 second)
        $executionTime = microtime(true) - $startTime;
        if ($executionTime > 1) {
            Log::warning("Slow request detected: {$request->fullUrl()} - {$executionTime} seconds");
        }
        
        // Add performance headers
        $response->headers->set('X-Processing-Time', round($executionTime, 3) . 's');
        
        return $response;
    }
}
