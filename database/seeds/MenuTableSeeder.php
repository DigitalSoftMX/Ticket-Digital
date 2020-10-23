<?php

use Illuminate\Database\Seeder;
use App\Menu;

class MenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menu = new Menu();
        $menu->name_modulo = "dashboard";
        $menu->desplegable = "0";
        $menu->ruta = "home";
        $menu->id_role = "0";
        $menu->icono = "icon-chart-pie-36";
        $menu->save();
        $menu->roles()->attach(['1', '2', '3']);

        $menu = new Menu();
        $menu->name_modulo = "Perfil";
        $menu->desplegable = "0";
        $menu->ruta = "profile";
        $menu->id_role = "0";
        $menu->icono = "icon-single-02";
        $menu->save();
        $menu->roles()->attach(['1', '2', '3']);

        $menu = new Menu();
        $menu->name_modulo = "Administradores";
        $menu->desplegable = "0";
        $menu->ruta = "admins";
        $menu->id_role = "1";
        $menu->icono = "supervisor_account";
        $menu->save();
        $menu->roles()->attach(['1', '2']);

        $menu = new Menu();
        $menu->name_modulo = "Despachadores";
        $menu->desplegable = "0";
        $menu->ruta = "dispatchers";
        $menu->id_role = "1";
        $menu->icono = "emoji_people";
        $menu->save();
        $menu->roles()->attach(['1', '2']);

        $menu = new Menu();
        $menu->name_modulo = "Clientes";
        $menu->desplegable = "0";
        $menu->ruta = "clients";
        $menu->id_role = "1";
        $menu->icono = "people_alt";
        $menu->save();
        $menu->roles()->attach(['1', '2', '3']);

        $menu = new Menu();
        $menu->name_modulo = "Validación de abonos";
        $menu->desplegable = "0";
        $menu->ruta = "balance";
        $menu->id_role = "0";
        $menu->icono = "icon-notes";
        $menu->save();
        $menu->roles()->attach('1');

        $menu = new Menu();
        $menu->name_modulo = "Estaciones";
        $menu->desplegable = "0";
        $menu->ruta = "stations";
        $menu->id_role = "1";
        $menu->icono = "local_gas_station";
        $menu->save();
        $menu->roles()->attach(['1', '2']);

        $menu = new Menu();
        $menu->name_modulo = "Movimientos";
        $menu->desplegable = "0";
        $menu->ruta = "movents";
        $menu->id_role = "0";
        $menu->icono = "icon-chart-bar-32";
        $menu->save();
        $menu->roles()->attach('1');
    }
}
