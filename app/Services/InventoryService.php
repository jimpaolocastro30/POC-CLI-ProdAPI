<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function stockIn(Product $product, int $quantity, ?string $remarks, User $user): InventoryTransaction
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive for stock in.');
        }

        return $this->processTransaction($product, TransactionType::StockIn, $quantity, $remarks, null, $user);
    }

    public function stockOut(Product $product, int $quantity, ?string $remarks, User $user): InventoryTransaction
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive for stock out.');
        }

        if ($product->current_stock < $quantity) {
            throw new InvalidArgumentException('Insufficient stock available.');
        }

        return $this->processTransaction($product, TransactionType::StockOut, -$quantity, $remarks, null, $user);
    }

    public function adjustment(Product $product, int $quantity, ?string $reason, User $user): InventoryTransaction
    {
        if ($quantity === 0) {
            throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        $newBalance = $product->current_stock + $quantity;
        if ($newBalance < 0) {
            throw new InvalidArgumentException('Adjustment would result in negative stock.');
        }

        return $this->processTransaction($product, TransactionType::Adjustment, $quantity, null, $reason, $user);
    }

    private function processTransaction(
        Product $product,
        TransactionType $type,
        int $quantityDelta,
        ?string $remarks,
        ?string $reason,
        User $user,
    ): InventoryTransaction {
        return DB::transaction(function () use ($product, $type, $quantityDelta, $remarks, $reason, $user) {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $oldStock = $lockedProduct->current_stock;
            $newStock = $oldStock + $quantityDelta;

            if ($newStock < 0) {
                throw new InvalidArgumentException('Insufficient stock available.');
            }

            $lockedProduct->update(['current_stock' => $newStock]);

            $transaction = InventoryTransaction::create([
                'product_id' => $lockedProduct->id,
                'transaction_type' => $type,
                'quantity' => abs($quantityDelta),
                'balance_after' => $newStock,
                'remarks' => $remarks,
                'reason' => $reason,
                'created_by' => $user->id,
            ]);

            $this->auditService->log(
                $user->id,
                $type->value,
                'inventory',
                ['product_id' => $lockedProduct->id, 'stock' => $oldStock],
                ['product_id' => $lockedProduct->id, 'stock' => $newStock, 'quantity' => abs($quantityDelta)],
            );

            return $transaction->load(['product', 'creator']);
        });
    }
}
