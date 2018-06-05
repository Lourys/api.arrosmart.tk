<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->routeMiddleware([
  'auth' => App\Http\Middleware\AuthCheckMiddleware::class,
  'systemCheck' => App\Http\Middleware\SystemCheckMiddleware::class,
]);


//////////////////////
// TOKEN MANAGEMENT //
//////////////////////

/* Creates a token to execute actions with an account */
$app->post('getAccessToken', [
  'uses' => 'AuthController@createToken'
]);

/* Regenerates the token */
$app->post('refreshAccessToken', [
  'uses' => 'AuthController@refreshToken'
]);

/* Checks if the token is still valid */
$app->post('checkAccessTokenValidity', [
  'uses' => 'AuthController@checkTokenValidity'
]);



//////////////////////
// USERS MANAGEMENT //
//////////////////////

/* Shows all users list */
$app->get('users', [
  'middleware' => 'auth:true',
  'uses' => 'UsersController@showAllUsers'
]);

/* Shows only one user */
$app->get('user/{id}', [
  'middleware' => 'auth:true',
  'uses' => 'UsersController@showOneUser'
]);
$app->get('user', [
  'middleware' => 'auth',
  'uses' => 'UsersController@showOneUser'
]);

/* Creates a new user */
$app->post('users', [
  'uses' => 'UsersController@addUser'
]);

/* Changes user's data */
$app->put('user', [
  'middleware' => 'auth',
  'uses' => 'UsersController@editUser'
]);
$app->put('user/{id}', [
  'middleware' => 'auth:true',
  'uses' => 'UsersController@editUser'
]);

/* Changes user's settings */
$app->put('user/settings', [
  'middleware' => 'auth',
  'uses' => 'UsersController@editUserSettings'
]);
$app->put('user/{id}/settings', [
  'middleware' => 'auth:true',
  'uses' => 'UsersController@editUserSettings'
]);



//////////////////////
// TOKEN MANAGEMENT //
//////////////////////

/* Shows schedule program */
$app->get('schedule', [
  'middleware' => 'auth',
  'uses' => 'ScheduleController@getSchedule'
]);

/* Changes schedule system data */
$app->put('schedule', [
  'middleware' => 'auth',
  'uses' => 'ScheduleController@editSchedule'
]);



///////////////////////
// SYSTEM MANAGEMENT //
///////////////////////

/* Add some weather data */
$app->post('system/addData', [
  'middleware' => 'systemCheck',
  'uses' => 'SystemController@addData'
]);

/* Get some weather data */
$app->get('system/getData', [
  'middleware' => 'auth',
  'uses' => 'SystemController@getData'
]);

/* Get raw weather data */
$app->get('system/getRawData', [
  'middleware' => 'auth',
  'uses' => 'SystemController@getRawData'
]);



/////////////////
//// GENERAL ////
/////////////////
$app->get('/', function () use ($app) {
  abort(403, 'Unauthorized action.');
});