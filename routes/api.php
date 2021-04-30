<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('register','App\Http\Controllers\RegisterController@register');
Route::post('login', 'App\Http\Controllers\RegisterController@login');
//send notification
Route::post('push-notification', 'App\Http\Controllers\Notification\PushNotificationController@sendPushNotification');
//store token
Route::post('save-token', 'App\Http\Controllers\Notification\PushNotificationController@saveToken');
//show all notification for one user
Route::get('display-notifications', 'App\Http\Controllers\Notification\PushNotificationController@displayNotifications');
//show notification content
Route::get('show-notification/{id}', 'App\Http\Controllers\Notification\PushNotificationController@showNotification');
