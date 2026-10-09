<?php

namespace App\Http\Requests\Admin;

use App\Rules\PersonName;
use Illuminate\Foundation\Http\FormRequest;

class StoreOnlineOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'customer_name' => ['required', 'string', 'max:120', new PersonName],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+][0-9+\s-]*$/'],
            'shipping_address' => ['required', 'string'],
            'landmark' => ['nullable', 'string', 'max:150'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'courier' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'in:cash,gcash'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:80'],
            'confirm_payment' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'The customer name is required.',
            'items.required' => 'Add at least one item to the order.',
            'items.min' => 'Add at least one item to the order.',
        ];
    }
}