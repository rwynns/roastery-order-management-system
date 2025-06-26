<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
        // return $this->user()->hasPermission('inventory.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id|unique:inventories,product_id',
            'current_stock' => 'required|integer|min:0',
            'reserved_stock' => 'nullable|integer|min:0',
            'last_cost' => 'nullable|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Selected product does not exist',
            'product_id.unique' => 'Inventory for this product already exists',
            'current_stock.required' => 'Current stock is required',
            'current_stock.min' => 'Stock cannot be negative',
            'last_cost.min' => 'Cost cannot be negative'
        ];
    }
}
