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
        $scheduleStart = array('00:01', '06:01', '14:01', '22:01');
        $scheduleEnd = array('06:00', '14:00', '22:00', '00:00');
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
        $this->saveSchedule(2, $scheduleStart[1], '15:00', 2);
        // Turno 3
        $this->saveSchedule(3, '15:01', $scheduleEnd[3], 2);
        //Estacion ELE
        // Turno 1
        $this->saveSchedule(1, $scheduleStart[1], '07:00', 4);
        // Turno 2
        $this->saveSchedule(2, '07:01', '15:00', 4);
        // Turno 3
        $this->saveSchedule(3, '15:01', '23:00', 4);
        // Turno 4
        $this->saveSchedule(4, '23:01', $scheduleEnd[3], 4);
        // Estacion CSE
        // Turno 1
        $this->saveSchedule(1, $scheduleStart[1], '07:00', 7);
        // Turno 2
        $this->saveSchedule(2, '07:01', '15:00', 7);
        // Turno 3
        $this->saveSchedule(3, '15:01', $scheduleEnd[2], 7);
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
