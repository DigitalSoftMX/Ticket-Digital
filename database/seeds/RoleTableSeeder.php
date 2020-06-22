<?php

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
        $role = new Role();
        $role->name = "admin_master";
        $role->description = "Administrador de la empresa DigitalSoft";
        $role->display_name="Administrador Master";
        $role->save();

        $role = new Role();
        $role->name = "admin_eucomb";
        $role->description = "Administrador de la empresa Eucomb";
        $role->display_name="Administrador Eucomb";
        $role->save();

        $role = new Role();
        $role->name = "admin_estacion";
        $role->description = "Administrador Eucomb para Vales y Premios";
        $role->display_name="Administrador Eucomb Vales y Premios";
        $role->save();

        $role = new Role();
        $role->name = "despachador";
        $role->description = "No tiene acceso al sistema";
        $role->display_name="Despachadores de Gasolina";
        $role->save();

        $role = new Role();
        $role->name = "usuario";
        $role->description = "Usuarios";
        $role->display_name="Usuarios o Clientes";
        $role->save();
    }
}
