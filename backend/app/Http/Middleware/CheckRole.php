<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if(!auth()->check()) {
            return ResponseHelper::unauthorized('Authentication required.');
        }

        $user = auth()->user();

        // Check if user has any of the required roles
        if (!in_array($user->role, $roles)) {
            return ResponseHelper::forbidden(
                'You do not have permission to access this resource.',
                [
                    'required roles' => $roles,
                    'your roles' => $user->role,
                ]
                );
        }

        return $next($request);
    }
}