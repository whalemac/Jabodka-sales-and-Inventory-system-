<?php

namespace App\Http\Requests\Admin;

use App\Rules\PersonName;
use Illuminate\Foundation\Http\FormRequest;

class StoreWalkInOrderRequest extends FormRequest
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
            'walk_in_name' => ['nullable', 'string', 'max:100', new PersonName],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+][0-9+\s-]*$/'],
            'payment_method' => ['required', 'in:cash,gcash'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:80'],
            'issue_receipt' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one item to the sale.',
            'items.min' => 'Add at least one item to the sale.',
        ];
    }
}