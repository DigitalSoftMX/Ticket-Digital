<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NameFreightRequest extends FormRequest
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
            'name' => [
                'required','min:4'
            ],
            'rfc' => [
                'required','min:12'
            ],
            'cre' => [
                'required','min:8'
            ],
            'telefono' => [
                'required','min:10'
            ],
            'direccion' => [
                'required','min:4'
            ],
            'contacto' => [
                'required','min:4'
            ],
    
        ];
    }
}
