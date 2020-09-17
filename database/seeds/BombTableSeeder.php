<?php

use App\Api\Bomb;
use App\Api\Island;
use Illuminate\Database\Seeder;

class BombTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $islands = Island::all();
        foreach ($islands as $island) {
            for ($j = 1; $j < 3; $j++) {
                $bomb = new Bomb();
                $bomb->no_bomb = $j;
                $bomb->island_id = $island->id;
                $bomb->save();
            }
        }
    }
}
