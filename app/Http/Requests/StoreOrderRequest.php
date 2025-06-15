<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'required|string|max:255|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
            'min_stock_level' => 'integer|min:0',
            'initial_stock' => 'integer|min:0'
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function message(): array
    {
        return [
            'name.required' => 'Product name is required',
            'sku.required' => 'SKU is required',
            'sku.unique' => 'SKU alredy exists',
            'price.required' => 'Price is required',
            'category_id.required' => 'Category is required',
            'category_id.exists' => 'Selected category does not exist'
        ];
    }
}
