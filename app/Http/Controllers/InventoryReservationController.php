<?php

namespace App\Http\Controllers;

use App\Actions\ReserveProduct;
use App\Http\Requests\StoreInventoryReservationRequest;
use App\Models\OrderLine;
use Symfony\Component\HttpFoundation\RedirectResponse;

class InventoryReservationController extends Controller
{
    public function store(StoreInventoryReservationRequest $request, ReserveProduct $reserveProduct): RedirectResponse
    {
        $validated = $request->validated();

        $reserveProduct->handle(
            productId: OrderLine::find($validated['order_line_id'])->product_id,
            orderLineId: $validated['order_line_id'],
            userId: auth()->user()->id,
            warehouseId: $validated['warehouse_id'],
            quantity: $validated['quantity']
        );

        return back();
    }
}
