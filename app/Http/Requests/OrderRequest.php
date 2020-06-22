<?php

namespace App\Http\Requests;

use App\Order;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
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

            'estacion_id' => [
                'required'
            ],
            'terminal_id' => [
                'nullable'
            ],
            'status_id' => [
                'required'
            ],
            'producto' => [
                'required'
            ],
            'so_number' => [
                'nullable'
            ],
            'cantidad_lts' => [
                'required'
            ],
            'costo_aprox' => [
                'required'
            ],
            'dia_entrega' => [
                'required'
            ],
            'po' => [
                'nullable'
            ],

        ];
    }
}
