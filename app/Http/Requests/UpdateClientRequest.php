<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Gate::allows('edit-clients');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $client = $this->route('client');
        $clientId = $client->id;

        return [
            'name' => 'required|string|max:150',
            'document' => [
                'required',
                'string',
                'max:50',
                'unique:clients,document,'.$clientId,
                function ($attribute, $value, $fail) use ($client) {
                    if ($client->orders()->exists() && $value !== $client->document) {
                        $fail('El documento no se puede modificar porque este cliente ya tiene órdenes asociadas.');
                    }
                },
            ],
            'phone' => 'nullable|string|max:30',
        ];
    }
}
