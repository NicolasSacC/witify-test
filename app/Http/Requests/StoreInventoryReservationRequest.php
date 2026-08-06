<?php

namespace App\Http\Requests;

use App\Models\InventoryBalance;
use App\Models\InventoryReservation;
use App\Models\OrderLine;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryReservationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'order_line_id' => ['required', 'integer', 'exists:order_lines,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
