<?php

namespace App\Http\Requests;

use App\Terminal;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class TerminalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

            'razon_social' => [
                'required', 'min:3'
            ],
            'status' => [
                'required',
            ],
            
        ];
    }
}
