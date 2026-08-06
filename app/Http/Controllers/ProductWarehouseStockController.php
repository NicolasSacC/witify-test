<?php

namespace App\Http\Controllers;

use App\Actions\ListWarehousesWithProductStock;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductWarehouseStockController extends Controller
{
    public function index(Product $product, ListWarehousesWithProductStock $listWarehousesWithProductStock): JsonResponse
    {
        return response()->json([
            'warehouses' => $listWarehousesWithProductStock->handle($product),
        ]);
    }
}
