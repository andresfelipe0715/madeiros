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
        return Gate::allows('edit-orders');
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
            'notes' => 'nullable|string',
        ];
    }
}
