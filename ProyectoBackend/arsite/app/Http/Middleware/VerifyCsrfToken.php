<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Symfony\Component\HttpFoundation\Response;

class VerifyCsrfToken extends Middleware
{
    /**
     * Handle an incoming request.
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
    
    
    //public function handle(Request $request, Closure $next): Response
    //{
    //    return $next($request);
    //}
}
