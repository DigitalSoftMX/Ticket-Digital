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
        $user->email = 'admin@material.com';
        $user->active = '1';
        $user->save();
        $user->roles()->attach($role_admin);

        $role_dispatcher = Role::where('name', 'despachador')->first();

        $user = new User();
        $user->name = 'Mariano';
        $user->first_surname = 'Alaez';
        $user->second_surname = 'Garrandes';
        $user->email = 'm.alaezg@eucomb.com';
        $user->sex = 'H';
        $user->phone = '2211703641';
        $user->active = 1;
        $user->password = bcrypt('despachador1');
        $user->save();
        $user->roles()->attach($role_dispatcher);

        $user = new User();
        $user->name = 'Diana';
        $user->first_surname = 'Vega';
        $user->second_surname = 'Armendariz';
        $user->email = 'd.vegaa@eucomb.com';
        $user->sex = 'M';
        $user->phone = '2252132017';
        $user->active = 1;
        $user->password = bcrypt('despachador2');
        $user->save();
        $user->roles()->attach($role_dispatcher);

        $user = new User();
        $user->name = 'Alda';
        $user->first_surname = 'Zumista';
        $user->second_surname = 'Menocal';
        $user->email = 'a.zumistam@eucomb.com';
        $user->sex = 'M';
        $user->phone = '2223816340';
        $user->active = 1;
        $user->password = bcrypt('despachador3');
        $user->save();
        $user->roles()->attach($role_dispatcher);

        $user = new User();
        $user->name = 'Patricio';
        $user->first_surname = 'Elguea';
        $user->second_surname = 'Tijera';
        $user->email = 'p.elgueat@eucomb.com';
        $user->sex = 'H';
        $user->phone = '2227012216';
        $user->active = 1;
        $user->password = bcrypt('despachador4');
        $user->save();
        $user->roles()->attach($role_dispatcher);

        $user = new User();
        $user->name = 'Micol';
        $user->first_surname = 'Anton';
        $user->second_surname = 'Llave';
        $user->email = 'm.antonl@eucomb.com';
        $user->sex = 'M';
        $user->phone = '2230642969';
        $user->active = 1;
        $user->password = bcrypt('despachador5');
        $user->save();
        $user->roles()->attach($role_dispatcher);

        $user = new User();
        $user->name = 'Valiet';
        $user->first_surname = 'Cerrajeria';
        $user->second_surname = 'Aleso';
        $user->email = 'v.cerrajeriaa@eucomb.com';
        $user->sex = 'H';
        $user->phone = '2298201687';
        $user->active = 1;
        $user->password = bcrypt('despachador6');
        $user->save();
        $user->roles()->attach($role_dispatcher);

        $user = new User();
        $user->name = 'Adrian';
        $user->first_surname = 'Berrondo';
        $user->second_surname = 'Urquiaga';
        $user->email = 'a.berrondou@eucomb.com';
        $user->sex = 'H';
        $user->phone = '2219327816';
        $user->active = 1;
        $user->password = bcrypt('despachador7');;
        $user->save();
        $user->roles()->attach($role_dispatcher);
    }
}
