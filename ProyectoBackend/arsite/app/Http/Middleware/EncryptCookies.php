<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * Handle an incoming request.
     * The names of the cookies that shouldn't be encrypted
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
