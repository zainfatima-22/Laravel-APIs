<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SyncPermissionsOnToken
{
    public function handle(Request $request, Closure $next): Response 
    {
        $user = $request->user();

        if ($user) {
            $guard = Auth::getDefaultDriver();

            if (! $user->hasRole('user')) {
                $user->assignRole('user');
            }

            $user->syncPermissions([
                'ticket_view',
                'ticket_create',
                'ticket_update',
                'ticket_delete',
            ]);
            $user->syncPermissions(['register', 'login', 'logout']);

            if ($guard) {
                Auth::shouldUse($guard);
            }
        }

        return $next($request);
    }
}