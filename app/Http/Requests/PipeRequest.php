<?php

namespace App\Http\Requests;

use App\Pipe;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PipeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_status' => [
                'required'
            ],
            'numero' => [
                'required'
            ],
            'numero_economico' => [
                'required'
            ],
            'capacidad' => [
                'required'
            ],
            'compartimentos' => [
                'required'
            ],
            'capacidad_compartimiento' => [
                'required', 'min:1'
            ],
            'contenedor_disponible' => [
                'nullable'
            ],
    
        ];
        
    }
}
