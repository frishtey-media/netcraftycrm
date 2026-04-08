<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class InventoryAuth
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('inventory')->check()) {
            return redirect()->route('inventory.login');
        }

        return $next($request);
    }
}
