<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseController
{
    public function index(): JsonResponse
    {
        $totalProducts = Product::count();
        $lowStockProducts = Product::where('current_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();
        $outOfStockProducts = Product::where('current_stock', '<=', 0)->count();
        $stockValue = Product::selectRaw('SUM(current_stock * unit_cost) as value')->value('value') ?? 0;
        $movementsToday = InventoryTransaction::whereDate('created_at', today())->count();

        return $this->success([
            'total_products' => $totalProducts,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'stock_value' => round((float) $stockValue, 2),
            'inventory_movements_today' => $movementsToday,
        ]);
    }
}
