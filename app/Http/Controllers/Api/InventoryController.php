<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Inventory\AdjustmentRequest;
use App\Http\Requests\Inventory\StockInRequest;
use App\Http\Requests\Inventory\StockOutRequest;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class InventoryController extends BaseController
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function stockIn(StockInRequest $request): JsonResponse
    {
        try {
            $product = Product::findOrFail($request->product_id);
            $transaction = $this->inventoryService->stockIn(
                $product,
                $request->quantity,
                $request->remarks,
                auth('api')->user(),
            );

            return $this->success($transaction, 'Stock in recorded.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function stockOut(StockOutRequest $request): JsonResponse
    {
        try {
            $product = Product::findOrFail($request->product_id);
            $transaction = $this->inventoryService->stockOut(
                $product,
                $request->quantity,
                $request->remarks,
                auth('api')->user(),
            );

            return $this->success($transaction, 'Stock out recorded.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function adjustment(AdjustmentRequest $request): JsonResponse
    {
        try {
            $product = Product::findOrFail($request->product_id);
            $transaction = $this->inventoryService->adjustment(
                $product,
                $request->quantity,
                $request->reason,
                auth('api')->user(),
            );

            return $this->success($transaction, 'Adjustment recorded.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $transactions = InventoryTransaction::with(['product', 'creator'])
            ->when($request->product_id, fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->transaction_type, fn ($q, $type) => $q->where('transaction_type', $type))
            ->when($request->from_date, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->to_date, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success($transactions);
    }
}
