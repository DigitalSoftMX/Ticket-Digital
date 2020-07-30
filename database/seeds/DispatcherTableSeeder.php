<?php

use App\Dispatcher;
use Illuminate\Database\Seeder;

class DispatcherTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i < 9; $i++) {
            $dispatcher = new Dispatcher();
            while (true) {
                $dispatcher_id = rand(10000000, 99999999);
                $dispatcher->dispatcher_id = 'GD-' . $dispatcher_id;
                if (!(Dispatcher::where('dispatcher_id', $dispatcher->dispatcher_id)->exists())) {
                    break;
                }
            }
            $dispatcher->user_id = $i + 1;
            $dispatcher->station_id = $i;
            $dispatcher->save();
        }
    }
}
