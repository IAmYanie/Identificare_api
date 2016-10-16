<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::resource('users', 'UserController');
// Route::post('user/login', 'UserController@login');
// Route::get('user/me', 'UserController@me');

// Route::resource('plants', 'PlantController');
// Route::post('plants/accept', 'PlantController@accept');
// Auth::routes();

// Route::get('/home', 'HomeController@index');
