<?php

use App\Models\InventoryBalance;
use App\Models\InventoryReservation;
use App\Models\OrderLine;
use App\Models\User;
use App\Models\Warehouse;

test('a reservation is created and the order line quantity_reserved is incremented', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $orderLine = OrderLine::factory()->create(['quantity_ordered' => 10]);
    $balance = InventoryBalance::factory()->create([
        'product_id' => $orderLine->product_id,
        'quantity' => 20,
    ]);

    $response = $this->post('/inventory/reservation', [
        'warehouse_id' => $balance->warehouse_id,
        'order_line_id' => $orderLine->id,
        'quantity' => 4,
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    $reservation = InventoryReservation::sole();
    expect($reservation->product_id)->toBe($orderLine->product_id)
        ->and($reservation->warehouse_id)->toBe($balance->warehouse_id)
        ->and($reservation->user_id)->toBe($user->id)
        ->and($reservation->quantity)->toBe(4);

    expect($orderLine->refresh()->quantity_reserved)->toBe(4);
});

test('a reservation cannot exceed the order line quantity ordered', function () {
    $this->actingAs(User::factory()->create());

    $orderLine = OrderLine::factory()->create(['quantity_ordered' => 5]);
    $balance = InventoryBalance::factory()->create([
        'product_id' => $orderLine->product_id,
        'quantity' => 100,
    ]);

    $response = $this->post('/inventory/reservation', [
        'warehouse_id' => $balance->warehouse_id,
        'order_line_id' => $orderLine->id,
        'quantity' => 6,
    ]);

    $response->assertSessionHasErrors('quantity');

    expect(InventoryReservation::count())->toBe(0)
        ->and($orderLine->refresh()->quantity_reserved)->toBe(0);
});

test('a reservation cannot exceed the stock available in the warehouse, accounting for existing reservations', function () {
    $this->actingAs(User::factory()->create());

    $orderLine = OrderLine::factory()->create(['quantity_ordered' => 10]);
    $warehouse = Warehouse::factory()->create();
    InventoryBalance::factory()->create([
        'product_id' => $orderLine->product_id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 5,
    ]);

    InventoryReservation::factory()->create([
        'product_id' => $orderLine->product_id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 3,
    ]);

    // 5 in stock - 3 already reserved = 2 available.
    $response = $this->post('/inventory/reservation', [
        'warehouse_id' => $warehouse->id,
        'order_line_id' => $orderLine->id,
        'quantity' => 3,
    ]);

    $response->assertSessionHasErrors('quantity');

    expect(InventoryReservation::where('order_line_id', $orderLine->id)->count())->toBe(0)
        ->and($orderLine->refresh()->quantity_reserved)->toBe(0);
});

test('the reserved quantity must be greater than zero', function (int $quantity) {
    $this->actingAs(User::factory()->create());

    $orderLine = OrderLine::factory()->create(['quantity_ordered' => 10]);
    $balance = InventoryBalance::factory()->create([
        'product_id' => $orderLine->product_id,
        'quantity' => 10,
    ]);

    $response = $this->post('/inventory/reservation', [
        'warehouse_id' => $balance->warehouse_id,
        'order_line_id' => $orderLine->id,
        'quantity' => $quantity,
    ]);

    $response->assertSessionHasErrors('quantity');

    expect(InventoryReservation::count())->toBe(0);
})->with([
    'zero' => 0,
    'negative' => -3,
]);

test('a reservation cannot exceed what remains to reserve on a partially-reserved line', function () {
    $this->actingAs(User::factory()->create());

    // 10 ordered, 6 already reserved by a previous reservation: 4 remain.
    $orderLine = OrderLine::factory()->create([
        'quantity_ordered' => 10,
        'quantity_reserved' => 6,
    ]);
    $balance = InventoryBalance::factory()->create([
        'product_id' => $orderLine->product_id,
        'quantity' => 100,
    ]);

    $response = $this->post('/inventory/reservation', [
        'warehouse_id' => $balance->warehouse_id,
        'order_line_id' => $orderLine->id,
        'quantity' => 5,
    ]);

    $response->assertSessionHasErrors('quantity');
    expect($orderLine->refresh()->quantity_reserved)->toBe(6);

    $this->post('/inventory/reservation', [
        'warehouse_id' => $balance->warehouse_id,
        'order_line_id' => $orderLine->id,
        'quantity' => 4,
    ])->assertSessionHasNoErrors();

    expect($orderLine->refresh()->quantity_reserved)->toBe(10);
});

test('the sum of reservations for a product in a warehouse never exceeds the physical stock', function () {
    $this->actingAs(User::factory()->create());

    $warehouse = Warehouse::factory()->create();
    $lineA = OrderLine::factory()->create(['quantity_ordered' => 10]);
    $lineB = OrderLine::factory()->create([
        'product_id' => $lineA->product_id,
        'quantity_ordered' => 10,
    ]);
    InventoryBalance::factory()->create([
        'product_id' => $lineA->product_id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 5,
    ]);

    $this->post('/inventory/reservation', [
        'warehouse_id' => $warehouse->id,
        'order_line_id' => $lineA->id,
        'quantity' => 3,
    ])->assertSessionHasNoErrors();

    // Only 2 units remain (5 stock - 3 reserved by line A): this must be rejected.
    $this->post('/inventory/reservation', [
        'warehouse_id' => $warehouse->id,
        'order_line_id' => $lineB->id,
        'quantity' => 3,
    ])->assertSessionHasErrors('quantity');

    $this->post('/inventory/reservation', [
        'warehouse_id' => $warehouse->id,
        'order_line_id' => $lineB->id,
        'quantity' => 2,
    ])->assertSessionHasNoErrors();

    expect((int) InventoryReservation::where('product_id', $lineA->product_id)->sum('quantity'))->toBe(5);
});
