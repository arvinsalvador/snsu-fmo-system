<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $r): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Profile retrieved successfully.', 'data' => new UserResource($r->user()->load('staffProfile.skills')), 'meta' => null]);
    }

    public function update(UpdateProfileRequest $r): JsonResponse
    {
        $u = $r->user();
        $u->fill($r->validated());
        $u->name = trim(collect([$u->first_name, $u->middle_name, $u->last_name, $u->suffix])->filter()->implode(' '));
        $u->save();

        return response()->json(['success' => true, 'message' => 'Profile updated successfully.', 'data' => new UserResource($u), 'meta' => null]);
    }

    public function password(ChangePasswordRequest $r): JsonResponse
    {
        $r->user()->update(['password' => Hash::make($r->validated('password'))]);
        $r->user()->tokens()->where('id', '!=', $r->user()->currentAccessToken()?->id)->delete();

        return response()->json(['success' => true, 'message' => 'Password changed successfully. Other device tokens were revoked.', 'data' => null, 'meta' => null]);
    }
}
