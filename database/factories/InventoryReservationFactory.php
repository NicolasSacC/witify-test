<?php

namespace Database\Factories;

use App\Models\InventoryReservation;
use App\Models\OrderLine;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryReservation>
 */
class InventoryReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderLine = OrderLine::factory()->create();

        return [
            'product_id' => $orderLine->product_id,
            'order_line_id' => $orderLine->id,
            'warehouse_id' => Warehouse::factory(),
            'user_id' => User::factory(),
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}
