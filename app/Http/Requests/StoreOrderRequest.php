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
        return \Illuminate\Support\Facades\Gate::allows('create-orders');
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'material' => 'required|string|max:255',
            'invoice_number' => 'required|string|max:50|unique:orders,invoice_number',
            'notes' => 'nullable|string|max:300',
            'lleva_herrajeria' => 'boolean',
            'lleva_manual_armado' => 'boolean',
            'stages' => 'required|array|min:1',
            'stages.*' => 'exists:stages,id',
            'order_file' => 'nullable|file|mimes:pdf|max:10240', // Max 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.exists' => 'El cliente seleccionado no es válido.',
            'material.required' => 'El material es obligatorio.',
            'stages.required' => 'Debe seleccionar al menos una etapa.',
            'stages.min' => 'Debe seleccionar al menos una etapa.',
            'stages.*.exists' => 'Una de las etapas seleccionadas no es válida.',
            'order_file.file' => 'El archivo de la orden debe ser un archivo válido.',
            'order_file.mimes' => 'El archivo de la orden debe ser un PDF.',
            'order_file.max' => 'El archivo de la orden no debe pesar más de 10MB.',
            'notes.max' => 'Las notas no deben exceder los 300 caracteres.',
        ];
    }
}
