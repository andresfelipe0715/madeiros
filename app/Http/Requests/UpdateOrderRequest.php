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
        $order = $this->route('order');
        $orderId = is_object($order) ? $order->id : $order;
        $isDelivered = is_object($order) ? $order->delivered_at : \App\Models\Order::where('id', $order)->whereNotNull('delivered_at')->exists();

        if ($isDelivered) {
            return [
                'materials' => 'required|array|min:1',
                'materials.*.id' => 'required|exists:order_materials,id',
                'materials.*.actual_quantity' => 'required|numeric|min:0',
            ];
        }

        return [
            'invoice_number' => 'required|string|max:50|unique:orders,invoice_number,' . $orderId,
            'notes' => 'nullable|string|max:300',
            'lleva_herrajeria' => 'boolean',
            'lleva_manual_armado' => 'boolean',
            'materials' => 'required|array|min:1',
            'materials.*.id' => 'nullable|exists:order_materials,id',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.estimated_quantity' => 'required|numeric|min:0.01',
            'materials.*.actual_quantity' => 'nullable|numeric|min:0',
            'materials.*.notes' => 'nullable|string|max:50',
            'materials.*.cancelled' => 'nullable|boolean',
            'order_file' => 'nullable|file|mimes:pdf|max:10240', // Max 10MB
        ];
    }
}
