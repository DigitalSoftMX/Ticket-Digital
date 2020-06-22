<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FreightRequest extends FormRequest
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
            'id_freights' => [
                'nullable'
            ],
            'id_estacion' => [
                'nullable'
            ],
            'id_pipa' => [
                'required'
            ],
            'id_tractor' => [
                'required'
            ],
            'id_chofer' => [
                'required'
            ],
        ];
    }
}
