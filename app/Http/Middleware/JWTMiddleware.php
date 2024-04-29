<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get token from header Authorization
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(
                ['message' => 'Token not provided'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        try {
            // Authenticate by token
            $user = JWTAuth::parseToken()->authenticate($token);
        } catch (\Throwable) {

            return response()->json(
                ['message' => 'Invalid Token'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        // Add authenticatedUser as new property of request
        $request->request->add(['authenticatedUser' => $user]);

        return $next($request);
    }
}
