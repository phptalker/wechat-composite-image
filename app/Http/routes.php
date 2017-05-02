<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'wechat'], function () {
    Route::any('/serve', "WechatController@serve");
    Route::any('/menu', "WechatController@setMenu");
});

Route::group(['prefix' => 'test'], function () {
    Route::any('/image', "TestController@image2");
});