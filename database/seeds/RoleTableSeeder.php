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
        // Accediendo a las estaciones de Eucomb en su base de datos lealtadd_eucomb
        $EucombRoles = EucombRole::all();
        // Asignando los roles de Eucomb a la base de datos Ticket Digital
        foreach ($EucombRoles as $EucombRol) {
            $role = new Role();
            $role->name = $EucombRol->name;
            $role->description = $EucombRol->description;
            $role->display_name = $EucombRol->display_name;
            $role->save();
        }
    }
}
