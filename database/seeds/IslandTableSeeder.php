<?php

use App\Api\Island;
use App\Station;
use Illuminate\Database\Seeder;

class IslandTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $stations = Station::all();
        foreach ($stations as $station) {
            for ($i = 1; $i < 3; $i++) {
                $island = new Island();
                $island->no_island = $i;
                $island->station_id = $station->id;
                $island->save();
            }
        }
    }
}
