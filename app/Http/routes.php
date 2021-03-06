<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::any("/recharge/","TreasureController@recharge");
Route::any("/recharge_return","TreasureController@recharge_return");
Route::any("/recharge_notify","TreasureController@recharge_notify");
