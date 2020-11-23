<?php

namespace App\Http\Controllers;

use App\User;
use App\Role;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the users
     *
     * @param  \App\User  $model
     * @return \Illuminate\View\View
     */
    public function index(User $model, Request $request)
    {
        // Consultando a los usuarios de la base de datos y enviando a la vista user.index
        $request->user()->authorizeRoles(['admin_master', 'admin_eucomb', 'admin_estacion', 'usuario']);
        return view('users.index', ['users' => $model::all()]);
    }

    /**
     * Show the form for creating a new user
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request, Role $roles)
    {
        $request->user()->authorizeRoles(['admin_master']);
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage
     *
     * @param  \App\Http\Requests\UserRequest  $request
     * @param  \App\User  $model
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(UserRequest $request, User $model)
    {
        $request->user()->authorizeRoles(['admin_master']);
        $model->create($request->merge(['password' => Hash::make($request->get('password'))])->all());
        $ultimo_registro = $model::get()->last();
        $user = $model::find($ultimo_registro->id);
        for ($i = 0; $i < count($request->razon_social); $i++) {
            $user->estacions()->attach($request->razon_social[$i]);
        }
        $user->roles()->attach($request->rol);

        return redirect()->route('user.index')->withStatus(__('Usuario creado exitosamente.'));
    }

    /**
     * Show the form for editing the specified user
     *
     * @param  \App\User  $user
     * @return \Illuminate\View\View
     */
    public function edit(User $user, Request $request, Role $roles)
    {
        $request->user()->authorizeRoles(['admin_master']);
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage
     *
     * @param  \App\Http\Requests\UserRequest  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UserRequest $request, User  $user)
    {
        $request->user()->authorizeRoles(['admin_master']);

        $rol_actual = "";

        foreach ($user->roles as $rol) {
            $rol_actual = $rol->id;
        }

        $hasPassword = $request->get('password');
        $user->update(
            $request->merge(['password' => Hash::make($request->get('password'))])
                ->except([$hasPassword ? '' : 'password'])
        );

        $user->roles()->updateExistingPivot($rol_actual, ['role_id' => $request->rol]);

        return redirect()->route('user.index')->withStatus(__('Usuario actualizado con éxito.'));
    }

    /**
     * Remove the specified user from storage
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user, Request $request)
    {
        $request->user()->authorizeRoles(['admin_master']);

        $user->delete();

        return redirect()->route('user.index')->withStatus(__('Usuario eliminado con éxito.'));
    }
}
