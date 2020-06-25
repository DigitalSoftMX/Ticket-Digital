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

// Rutas del main, princial, dashboard o home
Route::get('main', 'Api\MainController@main')->middleware('jwtAuth');
// Ruta para ver los abonos realizador por el usuario
Route::get('payments','Api\MainController@listPersonalPayments')->middleware('jwtAuth');
// Ruta para realizar un abono a la cuenta del cliente
Route::get('balance','Api\MainController@mainBalance')->middleware('jwtAuth');
Route::post('balance/pay', 'Api\MainController@pay')->middleware('jwtAuth');
