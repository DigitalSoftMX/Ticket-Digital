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
        /* $user->username = 'Invitado'; Falta el username */
        $user->password = bcrypt('1234567890Invitado*');
        $user->sex = 'H';
        $user->phone = '';
        $user->email = 'admin@material.com';
        $user->active = '1';
        $user->birthdate='01-01-0000';
        $user->remember_token = '';
        $user->email_verified_at = now();
        $user->created_at = now();
        $user->updated_at = now();
        $user->save();
        $user->roles()->attach($role_admin);
    }
}
