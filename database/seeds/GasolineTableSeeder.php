<?php

use App\Gasoline;
use Illuminate\Database\Seeder;

class GasolineTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $arrayGasoline = ['Margna', 'Premium', 'Diésel'];
        foreach ($arrayGasoline as $g) {
            $gasoline = new Gasoline();
            $gasoline->name = $g;
            $gasoline->save();
        }
    }
}
