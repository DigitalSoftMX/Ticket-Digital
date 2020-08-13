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
        $gasoline = new Gasoline();
        $gasoline->name = 'Magna';
        $gasoline->save();

        $gasoline = new Gasoline();
        $gasoline->name = 'Premium';
        $gasoline->save();

        $gasoline = new Gasoline();
        $gasoline->name = 'Diésel';
        $gasoline->save();
    }
}
