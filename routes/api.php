<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// Rutas del usuario, login, registro y cierre de sesion
Route::post('login', 'Api\AuthController@login');
Route::post('register', 'Api\AuthController@register');
Route::get('logout', 'Api\AuthController@logout');

Route::group(['middleware' => ['jwtAuth']], function () {
    // Rutas del main, princial, dashboard o home
    Route::get('main', 'Api\MainController@main');
    // Ruta para ver los abonos realizador por el usuario
    Route::get('payments', 'Api\MainController@getPersonalPayments');
    // Ruta para realizar un abono a la cuenta del cliente
    Route::get('balance', 'Api\MainController@mainBalance');
    Route::post('balance/pay', 'Api\MainController@addBalance');
    // Ruta para usar saldo del abono personal del usuario
    Route::get('balance/use', 'Api\MainController@useBalance');
    // Obtiene la lista de los pagos recibidos
    Route::get('balance/getlistreceived', 'Api\MainController@listReceivedPayments');
    // Ruta para usar saldo enviado por otro usuario
    Route::get('balance/getlistreceived/use', 'Api\MainController@useSharedBalance');
    // Ruta oara buscar un contacto
    Route::get('balance/contact', 'Api\MainController@lookingForContact');
    // Obtener lista de contactos, agregar un contacto a la lista, enviar saldo a un contacto agregado o no
    Route::post('balance/contact/add', 'Api\MainController@addContact');
    Route::get('balance/contact/getlist', 'Api\MainController@getContact');
    Route::post('balance/contact/sendbalance', 'Api\MainController@sendBalance');
    // Rutas para obtener historiales
    Route::get('balance/history', 'Api\MainController@history');
});
