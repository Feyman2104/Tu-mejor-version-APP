<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddMealItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real se hace en el controller
        return true;
    }

    public function rules(): array
    {
        return [
            'food_id'  => ['required', 'integer', 'exists:foods,id'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:9999'],
            'unit'     => ['required', 'string', 'in:g,kg,oz,ml,l,unidad'],
        ];
    }

    public function messages(): array
    {
        return [
            'food_id.exists'   => 'El alimento seleccionado no existe.',
            'quantity.min'     => 'La cantidad debe ser mayor a 0.',
            'unit.in'          => 'Unidad no válida. Usa: g, kg, oz, ml, l o unidad.',
        ];
    }
}
