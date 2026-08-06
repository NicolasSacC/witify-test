<?php

namespace App\Actions;

use App\Models\InventoryBalance;
use App\Models\InventoryReservation;
use App\Models\OrderLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveProduct implements Action
{
    /**
     * Reserve stock for an order line in a warehouse.
     *
     * The order line and the warehouse's inventory balance are each locked
     * for the duration of the transaction before their running totals
     * (quantity_reserved, and stock minus existing reservations) are read.
     * That serializes concurrent reservations against the same order line
     * or the same product/warehouse, so two requests can't both pass the
     * check and jointly over-reserve.
     *
     * @throws ValidationException when the order line or the warehouse no longer has enough left to reserve
     */
    public function handle(int $productId, int $orderLineId, int $userId, int $warehouseId, int $quantity): InventoryReservation
    {
        // $quantity is the quantity to reserve
        return DB::transaction(function () use ($productId, $orderLineId, $userId, $warehouseId, $quantity): InventoryReservation {
            $orderLine = OrderLine::query()
                ->whereKey($orderLineId)
                ->lockForUpdate()
                ->firstOrFail();

            // check if total amount is higher than amount ordered
            if ($orderLine->quantity_reserved + $quantity > $orderLine->quantity_ordered) {
                throw ValidationException::withMessages([
                    'quantity' => __('The total amount of reservations cannot exceed the quantity ordered.'),
                ]);
            }

            // check if stock available in warehouse
            $balance = InventoryBalance::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            $reserved = InventoryReservation::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->sum('quantity');

            $available = max(($balance->quantity ?? 0) - $reserved, 0);

            // check if any stock
            if ($available === 0) {
                throw ValidationException::withMessages([
                    'warehouse_id' => __('You cannot select a warehouse that doesn\'t have stock for this product.'),
                ]);
            }

            // check if trying to order more than available in warehouse (inventory - reserved)
            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => __('You cannot reserve more than the quantity available in this warehouse.'),
                ]);
            }

            $reservation = InventoryReservation::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'quantity' => $quantity,
                'order_line_id' => $orderLineId,
            ]);

            $orderLine->increment('quantity_reserved', $quantity);

            return $reservation;
        }, attempts: 3);
    }
}
