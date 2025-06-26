<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
        // return $this->user()->hasPermission('inventory.adjust');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inventory_id' => 'required|exists:inventories,id',
            'adjustment' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'last_cost' => 'nullable|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_id.required' => 'Inventory ID is required',
            'inventory_id.exists' => 'Selected inventory does not exist',
            'adjustment.required' => 'Adjustment quantity is required',
            'adjustment.not_in' => 'Adjustment cannot be zero',
            'reason.required' => 'Reason for adjustment is required',
            'last_cost.min' => 'Cost cannot be negative'
        ];
    }
}
