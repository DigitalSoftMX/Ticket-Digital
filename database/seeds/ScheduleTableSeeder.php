<?php

use App\Schedule;
use Illuminate\Database\Seeder;

class ScheduleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $schedulesStart = array('06:00', '12:00', '18:00', '00:00');
        $schedulesEnd = array('11:59', '17:59', '23:59', '5:59');
        for ($i = 1; $i < 8; $i++) {
            for ($j = 1; $j < 5; $j++) {
                $schedule = new Schedule();
                $schedule->name = 'Turno ' . $j;
                $schedule->start = $schedulesStart[$j - 1];
                $schedule->end = $schedulesEnd[$j - 1];
                $schedule->station_id = $i;
                $schedule->save();
            }
        }
    }
}
