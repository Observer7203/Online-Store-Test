<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{

        /**
     * Авторизация пользователя
     *
     * @group Auth
     * @unauthenticated
     *
     * @bodyParam email string required Email пользователя. Example: test@example.com
     * @bodyParam password string required Пароль пользователя. Example: secret123
     *
     * @response 200 {
     *   "user": {"id": 1, "name": "John Doe", "email": "test@example.com"},
     *   "token": "1|abc123xyz456"
     * }
     * @response 401 {"message": "Неверный логин или пароль."}
     */

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }

        /**
     * Выход из системы (удаление токена)
     *
     * @group Auth
     * @authenticated
     *
     * @response 200 {
     *   "message": "Вы вышли из системы"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }


        /**
     * Регистрация нового пользователя
     *
     * @group Auth
     * @unauthenticated
     *
     * @bodyParam name string required Имя пользователя. Example: John Doe
     * @bodyParam email string required Email пользователя. Example: test@example.com
     * @bodyParam password string required Пароль (мин. 6 символов). Example: secret123
     * @bodyParam password_confirmation string required Подтверждение пароля. Example: secret123
     *
     * @response 201 {
     *   "user": {"id": 1, "name": "John Doe", "email": "test@example.com"},
     *   "token": "1|abc123xyz456"
     * }
     */

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }
}



