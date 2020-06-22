<?php

namespace App\Http\Requests;

use App\Price;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PriceRequest extends FormRequest
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

            'id_estacion' => [
                'required'
            ],
            'extra' => [
                'required'
            ],
            'supreme' => [
                'required'
            ],
            'diesel' => [
                'required'
            ],
            'extra_u' => [
                'required'
            ],
            'supreme_u' => [
                'required'
            ],
            'diesel_u' => [
                'required'
            ],
        ];
    }
}
