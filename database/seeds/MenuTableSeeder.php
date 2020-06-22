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
        $menu->id_role = "1";
        $menu->icono = "dashboard";
        $menu->created_at = now();
        $menu->updated_at = now();
        $menu->save();
        $menu->roles()->attach('1');
        $menu->roles()->attach('2');
        $menu->roles()->attach('3');
        $menu->roles()->attach('4');

        $menu = new Menu();
        $menu->name_modulo = "Perfil";
        $menu->desplegable = "0";
        $menu->ruta = "profile";
        $menu->id_role = "1";
        $menu->icono = "account_circle";
        $menu->created_at = now();
        $menu->updated_at = now();
        $menu->save();
        $menu->roles()->attach('1');
        $menu->roles()->attach('2');
        $menu->roles()->attach('3');
        $menu->roles()->attach('4');

        $menu = new Menu();
        $menu->name_modulo = "Usuarios";
        $menu->desplegable = "0";
        $menu->ruta = "user";
        $menu->id_role = "1";
        $menu->icono = "perm_identity";
        $menu->created_at = now();
        $menu->updated_at = now();
        $menu->save();
        $menu->roles()->attach('1');
        $menu->roles()->attach('3');
    }
}
