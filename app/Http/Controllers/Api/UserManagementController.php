<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserManagementController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        return response()->json(['data' => User::query()->with('roles')->get()]);
    }
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $data = $request->validated();
        $user = User::create(collect($data)->except('roles')->all());
        $user->syncRoles($data['roles']);
        return response()->json(['data' => $user->load('roles')], 201);
    }
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $data = $request->validated();
        $user->update(collect($data)->except('roles')->all());
        if (array_key_exists('roles', $data)) { $user->syncRoles($data['roles']); }
        return response()->json(['data' => $user->load('roles')]);
    }
}
