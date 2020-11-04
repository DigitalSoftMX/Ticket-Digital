<?php

use App\Eucomb\Role as EucombRole;
use Illuminate\Database\Seeder;
use App\Role;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (EucombRole::all() as $eucombRole) {
            $role = new Role();
            $role->create(json_decode($eucombRole, true));
        }
    }
}
