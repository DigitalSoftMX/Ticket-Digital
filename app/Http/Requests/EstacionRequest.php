<?php

namespace App\Http\Requests;

use App\Estacion;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class EstacionRequest extends FormRequest
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
            'razon_social' => [
                'required', 'min:6'
            ],
            'rfc' => [
                'required', 'min:12'
            ],
            'cre' => [
                'nullable', 'min:3'
            ],
            'sh' => [
                'nullable'
            ],
            'nombre_sucursal' => [
                'required', 'min:3'
            ],
            'flete_r' => [
                'required', 'min:1'
            ],
            'flete_p' => [
                'required', 'min:1'
            ],
            'flete_d' => [
                'required', 'min:1'
            ],
            'ieps_r' => [
                'required', 'min:1'
            ],
            'ieps_p' => [
                'required', 'min:1'
            ],
            'ieps_d' => [
                'required', 'min:1'
            ],
            'utilidad_r' => [
                'required', 'min:1'
            ],
            'utilidad_p' => [
                'required', 'min:1'
            ],
            'utilidad_d' => [
                'required', 'min:1'
            ],

            'status' => [
                'nullable'
            ],

            'linea_credito' => [
                'nullable'
            ],

            'datos_fiscales' => [
                'nullable'
            ],

            'credito' => [
                'nullable'
            ],

            'credito_usado' => [
                'nullable'
            ],

            'saldo' => [
                'nullable'
            ],
            
            'dias_credito' => [
                'required', 'min:1'
            ],
            'retencion' => [
                'nullable', 'min:1'
            ],


            
            'codigo_postal' => [
                'nullable'
            ],
            'tipo_de_vialidad' => [
                'nullable'
            ],
            'nombre_de_vialidad' => [
                'nullable'
            ],
            'n_exterior' => [
                'nullable'
            ],
            'n_interior' => [
                'nullable'
            ],
            'nombre_colonia' => [
                'nullable'
            ],
            'nombre_localidad' => [
                'nullable'
            ],
            'nombre_municipio_o_demarcacion_territorial' => [
                'nullable'
            ],
            'nombre_entidad_federativa' => [
                'nullable'
            ],
            'entre_calle' => [
                'nullable'
            ],
            'y_calle' => [
                'nullable'
            ],
            
        ];
    }
}
