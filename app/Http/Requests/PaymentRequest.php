<?php

namespace App\Http\Requests;

use App\Payment;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
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
                'required', 'min:1'
            ],
            'cantidad' => [
                'required', 'min:3'
            ],
            'url' => [
                'required'
            ],
            'id_status' => [
                'required', 'min:1'
            ],
        ];
    }
}
