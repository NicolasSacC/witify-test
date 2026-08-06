<?php

namespace App\Actions;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveProduct implements Action
{
    public function handle(int $productId, int $orderLineId, int $userId, int $warehouseId, int $quantity)
    {
        return DB::transaction(function () use ($productId, $orderLineId, $userId, $warehouseId, $quantity) {

            $orderLine = OrderLine::find($orderLineId);
            $orderLine->quantity_reserved += $quantity;
            $orderLine->save();

            // check if total amount is higher than amount ordered
            if($orderLine->quantity_reserved > $orderLine->quantity_ordered) {
                throw ValidationException::withMessages([
                    'quantity' => __('The total amount of reservations cannot exceed the quantity ordered.')
                ]);
            }

            // check if stock available in warehouse
            $stock = InventoryBalance::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->value('quantity') ?? 0;

            $reserved = InventoryReservation::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->sum('quantity') ?? 0;

            $available = max($stock - $reserved, 0);

            // check if any stock
            if($available == 0) {
                throw ValidationException::withMessages([
                    'warehouse_id' => __('You cannot select a warehouse that doesn\'t have stock for this product.')
                ]);
            }

            // check if trying to order more than available in warehouse (inventory - reserved)
            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => __('You cannot reserve more than the quantity available in this warehouse.')
                ]);
            }

            // create reservation
            InventoryReservation::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'quantity' => $quantity,
                'order_line_id' => $orderLineId,
            ]);


        }, attempts: 3);
    }
}
