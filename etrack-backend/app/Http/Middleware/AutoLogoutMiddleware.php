<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoLogoutMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && $user->last_activity) {
            $inactiveTime = now()->diffInMinutes($user->last_activity);
            
            // Auto logout after 30 minutes of inactivity
            if ($inactiveTime > 30) {
                $user->tokens()->delete();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired due to inactivity',
                    'code' => 'SESSION_EXPIRED'
                ], 401);
            }
        }
        
        // Update last activity timestamp
        if ($user) {
            $user->update(['last_activity' => now()]);
        }
        
        return $next($request);
    }
}

