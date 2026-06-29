<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!in_array($user->role, $roles)) {
            // Redirect to appropriate dashboard based on role
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif (in_array($user->role, ['manager', 'staff'])) {
                return redirect()->route('staff.dashboard');
            }

            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
