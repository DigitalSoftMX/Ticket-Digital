<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([RoleTableSeeder::class]);
        $this->call([UserTableSeeder::class]);
        $this->call([StatusTableSeeder::class]);
        $this->call([StationTableSeeder::class]);
        $this->call([ScheduleTableSeeder::class]);
        $this->call([GasolineTableSeeder::class]);
        // $this->call([DispatcherTableSeeder::class]);
        $this->call([MenuTableSeeder::class]);
    }
}
