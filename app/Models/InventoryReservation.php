<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'warehouse_id', 'user_id', 'quantity', 'order_line_id'])]
class InventoryReservation extends Model
{
    //
}
