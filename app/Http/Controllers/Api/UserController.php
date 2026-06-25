<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserStatus;
use App\Http\Controllers\BaseController;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->success($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['status'] = UserStatus::Active;

        $user = User::create($data);

        if (! empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        $this->auditService->log(
            auth('api')->id(),
            'create',
            'users',
            null,
            $user->only(['id', 'name', 'email', 'status']),
        );

        return $this->success($user->load('roles'), 'User created.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->success($user->load('roles.permissions', 'auditLogs'));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $old = $user->only(['name', 'email', 'status']);
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        $this->auditService->log(
            auth('api')->id(),
            'update',
            'users',
            $old,
            $user->only(['name', 'email', 'status']),
        );

        return $this->success($user->load('roles'), 'User updated.');
    }

    public function destroy(User $user): JsonResponse
    {
        $old = $user->only(['id', 'name', 'email', 'status']);
        $user->update(['status' => UserStatus::Disabled]);

        $this->auditService->log(auth('api')->id(), 'disable', 'users', $old, ['status' => 'disabled']);

        return $this->success(null, 'User disabled.');
    }
}
