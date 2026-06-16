<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Users\StoreUserRequest;
use App\Http\Requests\Api\V1\Users\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $users) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $users = $this->users->paginate($request->only(['search', 'role', 'status', 'per_page']));

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $user = $this->users->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => ['user' => new UserResource($user)],
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data' => ['user' => new UserResource($user->load('roles', 'staffProfile.skills'))],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $user = $this->users->update($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => ['user' => new UserResource($user)],
        ]);
    }

    public function activate(User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $user = $this->users->setActive($user, true);

        return response()->json([
            'success' => true,
            'message' => 'User activated successfully.',
            'data' => ['user' => new UserResource($user)],
        ]);
    }

    public function deactivate(User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $user = $this->users->setActive($user, false);

        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully.',
            'data' => ['user' => new UserResource($user)],
        ]);
    }
}
