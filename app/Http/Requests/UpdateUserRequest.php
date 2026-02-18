<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:150'],
            'document' => [
                'required',
                'string',
                'max:50',
                'unique:users,document,' . $user->id,
                function ($attribute, $value, $fail) use ($user) {
                    if ($value !== $user->document) {
                        // Check if user has orders or logs
                        $hasOrders = \App\Models\Order::where('created_by', $user->id)
                            ->orWhere('delivered_by', $user->id)
                            ->orWhere('herrajeria_delivered_by', $user->id)
                            ->orWhere('manual_armado_delivered_by', $user->id)
                            ->exists();

                        $hasOrderStages = \App\Models\OrderStage::where('started_by', $user->id)
                            ->orWhere('completed_by', $user->id)
                            ->orWhere('pending_marked_by', $user->id)
                            ->exists();

                        if ($hasOrders || $hasOrderStages) {
                            $fail('El documento no se puede modificar porque este usuario ya tiene órdenes asociadas.');
                        }
                    }
                },
            ],
            'password' => ['nullable', 'string', 'min:6', 'max:30', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
            'active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',
            'document.required' => 'El documento es obligatorio.',
            'document.max' => 'El documento no puede superar los 50 caracteres.',
            'document.unique' => 'Este número de documento ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.max' => 'La contraseña no puede tener más de :max caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'role_id.required' => 'El rol es obligatorio.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
        ];
    }
}
