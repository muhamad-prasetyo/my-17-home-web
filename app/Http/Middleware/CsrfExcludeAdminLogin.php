<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CsrfExcludeAdminLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip CSRF validation for /admin/login route
        if ($request->is('admin/login')) {
            return $next($request);
        }
        
        return $next($request);
    }
}
