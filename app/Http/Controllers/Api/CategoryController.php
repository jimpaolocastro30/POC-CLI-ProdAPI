<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(Category::withCount('products')->orderBy('name')->get());
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        $this->auditService->log(auth('api')->id(), 'create', 'categories', null, $category->toArray());

        return $this->success($category, 'Category created.', 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $old = $category->toArray();
        $category->update($request->validated());

        $this->auditService->log(auth('api')->id(), 'update', 'categories', $old, $category->toArray());

        return $this->success($category, 'Category updated.');
    }
}
