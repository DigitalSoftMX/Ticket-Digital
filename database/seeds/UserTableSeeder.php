<?php

use Illuminate\Database\Seeder;
use App\Client;
use App\Eucomb\User as EucombUser;
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
        // Copiando usuarios de Eucbomb a Ticket
        foreach (EucombUser::all() as $eucombUser) {
            switch (count($surnames = explode(" ", $eucombUser->app_name))) {
                case 1:
                    $data['first_surname'] = $surnames[0];
                    break;
                case 2:
                    $data['first_surname'] = $surnames[0];
                    $data['second_surname'] = $surnames[1];
                    break;
                default:
                    $data['first_surname'] = $eucombUser->app_name;
                    break;
            }
            $data['id'] = $eucombUser->id;
            $data['name'] = $eucombUser->name;
            $data['username'] = $eucombUser->username;
            $data['email'] = $eucombUser->email;
            $data['sex'] = $eucombUser->sex;
            $data['phone'] = $eucombUser->telefono;
            $data['address'] = '';
            $data['active'] = $eucombUser->activo;
            $data['password'] = $eucombUser->password;
            $data['remember_token'] = $eucombUser->remember_token;
            $data['created_at'] = $eucombUser->created_at;
            $data['updated_at'] = $eucombUser->updated_at;
            $user = new User();
            $newUser = ($user->create($data));
            $newUser->roles()->attach($eucombUser->roles);
            // Registrando usuarios cliente
            foreach ($eucombUser->roles as $rol) {
                if ($rol->id == 5) {
                    $client = new Client();
                    $client->user_id = $eucombUser->id;
                    $client->current_balance = 0;
                    $client->shared_balance = 0;
                    $client->points = 0;
                    if ($eucombUser->image != null) {
                        $client->image = $eucombUser->image;
                    } else {
                        $client->image = $eucombUser->username;
                    }
                    $client->birthdate = $eucombUser->birthdate;
                    $client->save();
                    break;
                }
            }
            $data = [];
        }
        $role_admin = Role::where('name', 'admin_master')->first();
        $user = new User();
        $user->name = 'Invitado';
        $user->first_surname = 'qwerty';
        $user->second_surname = 'qwerty';
        $user->username = 'GI-20Invitado';
        $user->email = 'admin@material.com';
        $user->sex = 'H';
        $user->phone = '';
        $user->address = 'Vía Lorem ipsum, 3B';
        $user->active = '1';
        $user->password = bcrypt('1234567890Invitado*');
        $user->save();
        $user->roles()->attach($role_admin);
    }
}
