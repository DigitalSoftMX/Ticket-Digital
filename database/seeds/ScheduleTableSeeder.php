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
        $scheduleStart = array('00:00', '06:00', '14:00', '22:00');
        $scheduleEnd = array('05:59', '13:59', '21:59', '23:59');
        for ($i = 1; $i < 9; $i++) {
            for ($j = 1; $j < 5; $j++) {
                if ($i == 1 || $i == 3 || $i == 5 || $i == 6 || $i == 8) {
                    $this->saveSchedule($j, $scheduleStart[$j - 1], $scheduleEnd[$j - 1], $i);
                }
                if ($i == 2 && $j == 1) {
                    $this->saveSchedule($j, $scheduleStart[$j - 1], $scheduleEnd[$j - 1], $i);
                }
                if ($i == 7 && $j == 4) {
                    $this->saveSchedule($j, $scheduleStart[$j - 1], $scheduleEnd[$j - 1], $i);
                }
            }
        }
        // Estacion ETE
        // Turno 2
        $this->saveSchedule(2, $scheduleStart[1], '14:59', 2);
        // Turno 3
        $this->saveSchedule(3, '15:00', $scheduleEnd[3], 2);
        //Estacion ELE
        // Turno 1
        $this->saveSchedule(1, $scheduleStart[1], '06:59', 4);
        // Turno 2
        $this->saveSchedule(2, '07:00', '14:59', 4);
        // Turno 3
        $this->saveSchedule(3, '15:00', '22:59', 4);
        // Turno 4
        $this->saveSchedule(4, '23:00', $scheduleEnd[3], 4);
        // Estacion CSE
        // Turno 1
        $this->saveSchedule(1, $scheduleStart[1], '06:59', 7);
        // Turno 2
        $this->saveSchedule(2, '07:00', '14:59', 7);
        // Turno 3
        $this->saveSchedule(3, '15:00', $scheduleEnd[2], 7);
    }
    // Funcion para registrar horarios por estacion
    private function saveSchedule($turno, $start, $end, $station)
    {
        $schedule = new Schedule();
        $schedule->name = 'Turno ' . $turno;
        $schedule->start = $start;
        $schedule->end = $end;
        $schedule->station_id = $station;
        $schedule->save();
    }
}
