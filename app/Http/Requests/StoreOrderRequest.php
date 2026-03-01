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
            'invoice_number' => 'required|string|max:50|unique:orders,invoice_number',
            'notes' => 'nullable|string|max:300',
            'lleva_herrajeria' => 'boolean',
            'lleva_manual_armado' => 'boolean',
            'stages' => 'required|array|min:1',
            'stages.*.stage_id' => 'required|exists:stages,id',
            'stages.*.sequence' => 'required|integer|min:1',
            'order_file' => 'nullable|file|mimes:pdf|max:10240', // Max 10MB
            'materials' => 'required|array|min:1',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.estimated_quantity' => 'required|numeric|min:0.01',
            'materials.*.notes' => 'nullable|string|max:50',
            'special_services' => 'nullable|array',
            'special_services.*.service_id' => 'required|exists:special_services,id',
            'special_services.*.notes' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'El cliente es obligatorio.',
            'client_id.exists' => 'El cliente seleccionado no es válido.',
            'stages.required' => 'Debe seleccionar al menos una etapa.',
            'stages.min' => 'Debe seleccionar al menos una etapa.',
            'stages.*.exists' => 'Una de las etapas seleccionadas no es válida.',
            'materials.*.notes.max' => 'La nota del material no debe exceder los 50 caracteres.',
            'special_services.*.notes.max' => 'La nota del servicio especial no debe exceder los 50 caracteres.',
            'order_file.file' => 'El archivo de la orden debe ser un archivo válido.',
            'order_file.mimes' => 'El archivo de la orden debe ser un PDF.',
            'order_file.max' => 'El archivo de la orden no debe pesar más de 10MB.',
            'order_file.uploaded' => 'El archivo excedió el límite permitido por el servidor (PHP) o falló la conexión.',
            'notes.max' => 'Las notas no deben exceder los 300 caracteres.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $stages = $this->input('stages', []);
            if (! is_array($stages) || empty($stages)) {
                return;
            }

            $sequences = [];
            foreach ($stages as $stageData) {
                if (isset($stageData['sequence'])) {
                    $sequences[] = (int) $stageData['sequence'];
                }
            }

            sort($sequences);
            $expected = 1;
            foreach ($sequences as $seq) {
                if ($seq !== $expected) {
                    $validator->errors()->add('stages', 'La secuencia de las etapas debe ser consecutiva y única.');
                    break;
                }
                $expected++;
            }
        });
    }
}
