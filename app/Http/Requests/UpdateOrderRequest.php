<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! Gate::allows('edit-orders')) {
            return false;
        }

        $order = $this->route('order');

        // Block updates if order already has a delivery date
        if ($order && $order->delivered_at) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $orderId = $this->route('order')->id ?? $this->order;

        return [
            'invoice_number' => 'required|string|max:50|unique:orders,invoice_number,'.$orderId,
            'material' => 'required|string|max:255',
            'notes' => 'nullable|string|max:300',
            'lleva_herrajeria' => 'boolean',
            'lleva_manual_armado' => 'boolean',
        ];
    }
}
