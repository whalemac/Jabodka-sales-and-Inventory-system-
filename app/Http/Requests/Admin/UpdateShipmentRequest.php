<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipment_status' => ['sometimes', 'required', 'in:pending,preparing,shipped,in_transit,delivered,returned'],
            'tracking_number' => ['nullable', 'string', 'max:80'],
            'courier' => ['nullable', 'string', 'max:50'],
        ];
    }
}