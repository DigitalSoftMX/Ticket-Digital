<?php

use App\Eucomb\Station as EucombStation;
use App\Station;
use Illuminate\Database\Seeder;

class StationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Copiando de Eucomb a Ticket
        foreach (EucombStation::all() as $eucombStation) {
            $station = new Station();
            $station->id = $eucombStation->id;
            $station->name = $eucombStation->name;
            $station->address = $eucombStation->address;
            $station->phone = $eucombStation->telefono;
            $station->email = $eucombStation->correo;
            $station->total_timbres = $eucombStation->total_timbres;
            $station->total_facturas = $eucombStation->total_facturas;
            $station->id_empresa = $eucombStation->id_empresa;
            $station->id_type = $eucombStation->id_type;
            $station->number_station = $eucombStation->number_station;
            $station->active = $eucombStation->activo;
            $station->lealtad = true;
            $station->created_at = $eucombStation->created_at;
            $station->updated_at = $eucombStation->updated_at;
            $station->save();
        }
    }
}
