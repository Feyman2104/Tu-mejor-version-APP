<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMealItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:9999'],
            'unit'     => ['required', 'string', 'in:g,kg,oz,ml,l,unidad'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
            'unit.in'      => 'Unidad no válida. Usa: g, kg, oz, ml, l o unidad.',
        ];
    }
}
