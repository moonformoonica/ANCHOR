<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function token(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();
        if ($user === null || ! Hash::check($request->string('password'), $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }
        $abilities = $user->hasRole('admin') ? ['*'] : ['review:cases'];
        $token = $user->createToken($request->input('device_name', 'api-client'), $abilities)->plainTextToken;

        return response()->json(['token' => $token, 'token_type' => 'Bearer', 'roles' => $user->getRoleNames()]);
    }
}
