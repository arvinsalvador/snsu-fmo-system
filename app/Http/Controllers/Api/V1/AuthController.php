<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();
        $device = str($request->string('device_name')->value() ?: 'mobile-device')->squish()->limit(100);
        $token = $user->createToken($device)->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer', 'user' => new UserResource($user->load('staffProfile.skills')),
                'roles' => $user->getRoleNames()->values(), 'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                'authorized_scope' => ['campus' => 'SNSU Del Carmen Campus', 'building_ids' => [], 'department_ids' => []],
            ],
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $count = $request->user()->tokens()->count();
        $request->user()->tokens()->delete();

        return response()->json(['success' => true, 'message' => 'All devices logged out successfully.', 'data' => ['revoked_tokens' => $count], 'meta' => null]);
    }

    public function permissions(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Authorization details retrieved successfully.', 'data' => ['roles' => $request->user()->getRoleNames()->values(), 'permissions' => $request->user()->getAllPermissions()->pluck('name')->values(), 'scope' => ['campus' => 'SNSU Del Carmen Campus', 'building_ids' => [], 'department_ids' => []]], 'meta' => null]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
            'data' => null,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => [
                'user' => new UserResource($request->user()),
            ],
        ]);
    }
}
