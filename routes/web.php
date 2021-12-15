<?php

use Illuminate\Support\Facades\Route;
// Route::get('sumardoble','Api\BalanceController@sumar');
Route::get('/', function () {
	return view('auth.login');
});

Route::group(['middleware' => 'auth'], function () {
	Route::get('/', 'HomeController@index')->name('home')->middleware('auth');
});
Auth::routes();
