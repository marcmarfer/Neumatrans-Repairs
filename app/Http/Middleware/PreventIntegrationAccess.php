<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventIntegrationAccess
{
    /**
     * Block users with is_integration = true from web routes.
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->is_integration) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'No se puede acceder con un usuario que no sea administrador');
        }

        return $next($request);
    }
} 