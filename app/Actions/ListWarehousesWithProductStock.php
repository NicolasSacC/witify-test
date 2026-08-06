<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class ListWarehousesWithProductStock implements Action
{
    public function handle(Product $product): array
    {
        $totalsInventory = DB::table('inventory_balances')
            ->where('product_id', $product->id)
            ->selectRaw('warehouse_id, SUM(quantity) as units_in_stock')
            ->groupBy('warehouse_id')
            ->get()
            ->keyBy('warehouse_id');

        $totalsReserved = DB::table('inventory_reservations')
            ->where('product_id', $product->id)
            ->selectRaw('warehouse_id, SUM(quantity) as units_reserved')
            ->groupBy('warehouse_id')
            ->get()
            ->keyBy('warehouse_id');

        return Warehouse::query()
            ->orderBy('name')
            ->get()
            ->map(function (Warehouse $warehouse) use ($totalsInventory, $totalsReserved): array {
                $totalInventory = (int) $totalsInventory->get($warehouse->id)?->units_in_stock ?? 0;
                $totalReserved = (int) $totalsReserved->get($warehouse->id)?->units_reserved ?? 0;

                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'stock' => $totalInventory - $totalReserved,
                ];
            })
            ->all();
    }
}
