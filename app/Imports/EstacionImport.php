<?php

namespace App\Imports;

use App\Estacion;
use Maatwebsite\Excel\Concerns\ToModel;

class EstacionImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Estacion([
            //
        ]);
    }
}
