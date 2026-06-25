<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\AuditLog;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseController
{
    public function inventory(Request $request): JsonResponse
    {
        $products = Product::with(['category', 'supplier'])
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->low_stock, fn ($q) => $q->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category?->name,
                'supplier' => $p->supplier?->name,
                'current_stock' => $p->current_stock,
                'minimum_stock' => $p->minimum_stock,
                'unit_cost' => $p->unit_cost,
                'stock_value' => round($p->current_stock * $p->unit_cost, 2),
                'status' => $p->isOutOfStock() ? 'out_of_stock' : ($p->isLowStock() ? 'low_stock' : 'in_stock'),
            ]);

        return $this->success([
            'items' => $products,
            'summary' => [
                'total_items' => $products->count(),
                'total_value' => round($products->sum('stock_value'), 2),
            ],
        ]);
    }

    public function movements(Request $request): JsonResponse
    {
        $transactions = InventoryTransaction::with(['product', 'creator'])
            ->when($request->from_date, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->to_date, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($request->transaction_type, fn ($q, $type) => $q->where('transaction_type', $type))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 50));

        return $this->success($transactions);
    }

    public function audit(Request $request): JsonResponse
    {
        $logs = AuditLog::with('user')
            ->when($request->module, fn ($q, $module) => $q->where('module', $module))
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->from_date, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->to_date, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 50));

        return $this->success($logs);
    }
}
