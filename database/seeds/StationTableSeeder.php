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
        // Accediendo a las estaciones de Eucomb en su base de datos lealtadd_eucomb
        $EucombStations = EucombStation::all();
        // Asignando las estaciones de Eucomb a la base de datos Ticket Digital
        foreach ($EucombStations as $EucombStation) {
            $station = new Station();
            $station->name = $EucombStation->name;
            $station->address = $EucombStation->address;
            $station->phone = $EucombStation->telefono;
            $station->email = $EucombStation->correo;
            $station->type_id = $EucombStation->id_type;
            $station->comes_id = $EucombStation->id_comes;
            $station->number_station = $EucombStation->number_station;
            $station->save();
        }
    }
}
