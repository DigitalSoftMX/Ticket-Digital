<?php

use Illuminate\Database\Seeder;
use App\Role;
use App\User;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role_admin = Role::where('name', 'admin_master')->first();

        $user = new User();
        $user->name = 'Invitado';
        $user->first_surname = 'qwerty';
        $user->second_surname = 'qwerty';
        $user->password = bcrypt('1234567890Invitado*');
        $user->sex = 'H';
        $user->phone = '';
        $user->address = 'Vía Lorem ipsum, 3B';
        $user->email = 'admin@material.com';
        $user->active = '1';
        $user->save();
        $user->roles()->attach($role_admin);

        $role_dispatcher = Role::where('name', 'despachador')->first();
        // Despachador ficticio para la estacion 1
        $user = new User();
        $user->name = 'Mariano';
        $user->first_surname = 'Alaez';
        $user->second_surname = 'Garrandes';
        $user->email = 'm.alaezg@eucomb.com';
        $user->sex = 'H';
        $user->phone = '2211703641';
        $user->address = 'Calle Lorem ipsum, 205';
        $user->active = 1;
        $user->password = bcrypt('despachador1');
        $user->save();
        $user->roles()->attach($role_dispatcher);
        // Despachador ficticio para la estacion 2
        $user = new User();
        $user->name = 'Diana';
        $user->first_surname = 'Vega';
        $user->second_surname = 'Armendariz';
        $user->email = 'd.vegaa@eucomb.com';
        $user->sex = 'M';
        $user->phone = '2252132017';
        $user->address = 'Glorieta Lorem ipsum dolor, 243B 10ºB';
        $user->active = 1;
        $user->password = bcrypt('despachador2');
        $user->save();
        $user->roles()->attach($role_dispatcher);
        // Despachador ficticio para la estacion 3
        $user = new User();
        $user->name = 'Alda';
        $user->first_surname = 'Zumista';
        $user->second_surname = 'Menocal';
        $user->email = 'a.zumistam@eucomb.com';
        $user->sex = 'M';
        $user->phone = '2223816340';
        $user->address = 'Cuesta Lorem ipsum, 141';
        $user->active = 1;
        $user->password = bcrypt('despachador3');
        $user->save();
        $user->roles()->attach($role_dispatcher);
        // Despachador ficticio para la estacion 4
        $user = new User();
        $user->name = 'Patricio';
        $user->first_surname = 'Elguea';
        $user->second_surname = 'Tijera';
        $user->email = 'p.elgueat@eucomb.com';
        $user->sex = 'H';
        $user->phone = '2227012216';
        $user->address = 'Ronda Lorem, 187A';
        $user->active = 1;
        $user->password = bcrypt('despachador4');
        $user->save();
        $user->roles()->attach($role_dispatcher);
        // Despachador ficticio para la estacion 5
        $user = new User();
        $user->name = 'Micol';
        $user->first_surname = 'Anton';
        $user->second_surname = 'Llave';
        $user->email = 'm.antonl@eucomb.com';
        $user->sex = 'M';
        $user->phone = '2230642969';
        $user->address = 'Callejón Lorem, 134A 7ºD';
        $user->active = 1;
        $user->password = bcrypt('despachador5');
        $user->save();
        $user->roles()->attach($role_dispatcher);
        // Despachador ficticio para la estacion 6
        $user = new User();
        $user->name = 'Valiet';
        $user->first_surname = 'Cerrajeria';
        $user->second_surname = 'Aleso';
        $user->email = 'v.cerrajeriaa@eucomb.com';
        $user->sex = 'H';
        $user->phone = '2298201687';
        $user->address = 'Cañada Lorem, 6 1ºG';
        $user->active = 1;
        $user->password = bcrypt('despachador6');
        $user->save();
        $user->roles()->attach($role_dispatcher);
        // Despachador ficticio para la estacion 7
        $user = new User();
        $user->name = 'Adrian';
        $user->first_surname = 'Berrondo';
        $user->second_surname = 'Urquiaga';
        $user->email = 'a.berrondou@eucomb.com';
        $user->sex = 'H';
        $user->phone = '2219327816';
        $user->address = 'Carrera Lorem, 18';
        $user->active = 1;
        $user->password = bcrypt('despachador7');
        $user->save();
        $user->roles()->attach($role_dispatcher);
        // Despachador ficticio para la estacion 8
        $user = new User();
        $user->name = 'Sonia';
        $user->first_surname = 'Ambrosio';
        $user->second_surname = 'Izmendi';
        $user->email = 's.ambrosioi@eucomb.com';
        $user->sex = 'M';
        $user->phone = '2225865884';
        $user->address = 'Urbanización Lorem, 250';
        $user->active = 1;
        $user->password = bcrypt('despachador8');
        $user->save();
        $user->roles()->attach($role_dispatcher);
    }
}
