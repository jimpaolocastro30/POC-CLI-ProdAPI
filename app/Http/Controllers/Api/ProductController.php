<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = Product::with(['category', 'supplier'])
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->low_stock, fn ($q) => $q->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->success($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        $this->auditService->log(
            auth('api')->id(),
            'create',
            'products',
            null,
            $product->toArray(),
        );

        return $this->success($product->load(['category', 'supplier']), 'Product created.', 201);
    }

    public function show(Product $product): JsonResponse
    {
        return $this->success($product->load(['category', 'supplier', 'transactions']));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $old = $product->toArray();
        $product->update($request->validated());

        $this->auditService->log(
            auth('api')->id(),
            'update',
            'products',
            $old,
            $product->toArray(),
        );

        return $this->success($product->load(['category', 'supplier']), 'Product updated.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $old = $product->toArray();
        $product->delete();

        $this->auditService->log(auth('api')->id(), 'delete', 'products', $old, null);

        return $this->success(null, 'Product deleted.');
    }
}
