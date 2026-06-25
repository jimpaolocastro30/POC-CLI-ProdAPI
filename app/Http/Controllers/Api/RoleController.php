<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends BaseController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->where('guard_name', 'api')->get();

        return $this->success($roles);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'api',
        ]);

        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        $this->auditService->log(
            auth('api')->id(),
            'create',
            'roles',
            null,
            ['name' => $role->name, 'permissions' => $request->permissions],
        );

        return $this->success($role->load('permissions'), 'Role created.', 201);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $old = ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')];

        if ($request->has('name')) {
            $role->update(['name' => $request->name]);
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $this->auditService->log(
            auth('api')->id(),
            'update',
            'roles',
            $old,
            ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')],
        );

        return $this->success($role->load('permissions'), 'Role updated.');
    }
}
