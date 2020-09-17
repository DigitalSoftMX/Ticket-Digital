<?php

use App\Api\Status;
use Illuminate\Database\Seeder;

class StatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $statusArray = ['Pendiente', 'Activado', 'Denegado', 'Disponible', 'Compartido'];
        foreach ($statusArray as $s) {
            $status = new Status();
            $status->name = $s;
            $status->save();
        }
    }
}
