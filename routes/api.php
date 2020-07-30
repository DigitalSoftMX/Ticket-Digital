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

Route::group(['middleware' => 'jwtAuth'], function () {
    // Rutas del main, princial, dashboard o home
    Route::get('main', 'Api\MainController@main');
    // Ruta para ver los abonos realizador por el usuario
    Route::get('payments', 'Api\MainController@getPersonalPayments');
    // Ruta para realizar un abono a la cuenta del cliente
    Route::get('balance', 'Api\MainController@getListStations');
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
    Route::post('balance/contact/delete', 'Api\MainController@deleteContact');
    Route::get('balance/contact/getlist', 'Api\MainController@getListContacts');
    Route::post('balance/contact/sendbalance', 'Api\MainController@sendBalance');
    // Rutas para obtener historiales
    Route::get('balance/history', 'Api\MainController@history');
});
// Rutal para el usuario con rol despachador
Route::group(['middleware' => 'jwtAuth'], function () {
    // Rutas del main, princial, dashboard o home
    Route::get('maindispatcher', 'Api\DispatcherController@main');
    // Ruta para obtener los tipos de gasolina
    Route::get('gasolinelist','Api\DispatcherController@gasolineList');
    // Ruta temporal para hacer un cobro para el cliente
    Route::post('makepayment', 'Api\DispatcherController@makePayment');
    // Ruta para obtener los cobros totales actuales
    Route::get('getpaymentsnow','Api\DispatcherController@getPaymentsNow');
    // Ruta para obtener la lista de cobros por fecha
    Route::get('getlistpayments','Api\DispatcherController@getListPayments');
});
// Rutas para ver y editar perfiles de cliente y despachador
Route::group(['middleware' => 'jwtAuth'], function () {
    // Ruta para ver la informacion del despachador y cliente
    Route::get('profile', 'Api\UserController@index');
    // Ruta para editar la inforamcion del despachador y cliente
    Route::post('profile/update', 'Api\UserController@update');
});
