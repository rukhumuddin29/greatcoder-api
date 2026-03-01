<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $permissions = explode(',', $permission);
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $hasAny = false;
        foreach ($permissions as $p) {
            if ($user->hasPermission(trim($p))) {
                $hasAny = true;
                break;
            }
        }

        if (!$hasAny) {
            return response()->json([
                'message' => 'Unauthorized. Missing at least one of the following permissions: ' . $permission
            ], 403);
        }

        return $next($request);
    }
}
