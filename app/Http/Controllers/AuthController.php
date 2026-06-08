<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Services\TokenService;
use App\Models\User;
use App\DTO\UserDTO;
use App\DTO\AuthSuccessDTO;
use App\DTO\TokenListDTO;
use App\DTO\TokenInfoDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private TokenService $tokenService) {}

    /**
     * POST /api/auth/register
     * Регистрация нового пользователя
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'birthday' => $validated['birthday'],
        ]);

        return response()->json(UserDTO::fromModel($user), 201);
    }

    /**
     * POST /api/auth/login
     * Авторизация пользователя
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('username', $validated['username'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $tokens = $this->tokenService->generateTokens($user);

        return response()->json(
            new AuthSuccessDTO(
                accessToken: $tokens['access_token'],
                refreshToken: $tokens['refresh_token'],
                user: UserDTO::fromModel($user)
            ),
            200
        );
    }

    /**
     * GET /api/auth/me
     * Информация о текущем пользователе
     */
    public function me(Request $request)
    {
        $user = User::find($request->input('user_id'));

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(UserDTO::fromModel($user));
    }

    /**
     * POST /api/auth/out
     * Разлогирование (отзыв текущего токена)
     */
    public function out(Request $request)
    {
        $this->tokenService->revokeToken(
            $request->input('token_id'),
            $request->input('user_id')
        );

        return response()->json(['message' => 'Logged out'], 200);
    }

    /**
     * GET /api/auth/tokens
     * Список активных токенов пользователя
     */
    public function tokens(Request $request)
    {
        $activeTokens = $this->tokenService->getActiveTokens($request->input('user_id'));

        $tokenInfos = array_map(function ($token) {
            return new TokenInfoDTO(
                id: $token['id'],
                createdAt: $token['createdAt'],
                expiresAt: $token['expiresAt'],
                lastUsedAt: $token['lastUsedAt'] ?? null
            );
        }, $activeTokens);

        return response()->json(new TokenListDTO($tokenInfos));
    }

    /**
     * POST /api/auth/out_all
     * Разлогирование со всех устройств
     */
    public function outAll(Request $request)
    {
        $this->tokenService->revokeAllTokens($request->input('user_id'));

        return response()->json(['message' => 'All tokens revoked'], 200);
    }

    /**
     * POST /api/auth/refresh
     * Обновление токена доступа
     */
    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');
        
        if (!$refreshToken) {
            return response()->json(['message' => 'Refresh token required'], 400);
        }

        $payload = $this->tokenService->validateRefreshToken($refreshToken);

        if (!$payload) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $user = User::find($payload['user_id']);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Отзываем старый токен
        $this->tokenService->revokeToken($payload['token_id'], $user->id);

        // Генерируем новую пару
        $tokens = $this->tokenService->generateTokens($user);

        return response()->json([
            'accessToken' => $tokens['access_token'],
            'refreshToken' => $tokens['refresh_token'],
        ], 200);
    }

    /**
     * POST /api/auth/change-password
     * Изменение пароля (дополнительное требование)
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = User::find($request->input('user_id'));

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Проверяем текущий пароль
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 401);
        }

        // Обновляем пароль
        $user->update([
            'password' => $request->input('password'),
        ]);

        // Отзываем все токены пользователя (рекомендуется для безопасности)
        $this->tokenService->revokeAllTokens($user->id);

        return response()->json(['message' => 'Password changed successfully'], 200);
    }
}