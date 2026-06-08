<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TokenService;

class AuthMiddleware
{
    public function __construct(private TokenService $tokenService) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $payload = $this->tokenService->validateAccessToken($token);

        if (!$payload) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }

        $request->merge([
            'user_id' => $payload['user_id'],
            'token_id' => $payload['token_id'],
        ]);

        return $next($request);
    }
}