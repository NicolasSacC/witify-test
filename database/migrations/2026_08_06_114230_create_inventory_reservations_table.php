<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('user_id');
            $table->foreignId('order_line_id');
            $table->foreignId('warehouse_id');
            $table->unsignedInteger('quantity'); // j'utilise un integer car c'est le type utilisé par "inventory_balances". j'assume qu'on ne fera pas de réservation sur d'un integer plus grand que la balance totale de l'inventaire.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
