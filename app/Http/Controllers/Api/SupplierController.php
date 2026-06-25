<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;

class SupplierController extends BaseController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(Supplier::withCount('products')->orderBy('name')->get());
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->validated());

        $this->auditService->log(auth('api')->id(), 'create', 'suppliers', null, $supplier->toArray());

        return $this->success($supplier, 'Supplier created.', 201);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $old = $supplier->toArray();
        $supplier->update($request->validated());

        $this->auditService->log(auth('api')->id(), 'update', 'suppliers', $old, $supplier->toArray());

        return $this->success($supplier, 'Supplier updated.');
    }
}
