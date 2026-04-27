<?php

namespace App\Http\Middleware;


use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CallingUserAuth
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('calling_user')->check()) {
            return redirect('/calling/login');
        }

        return $next($request);
    }
}
