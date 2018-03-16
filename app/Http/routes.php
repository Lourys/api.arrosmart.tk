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
]);


$app->get('/', function () use ($app) {
  abort(403, 'Unauthorized action.');
});

//////////////////////
// TOKEN MANAGEMENT //
//////////////////////
$app->post('getAccessToken', [
  'uses' => 'AuthController@createToken'
]);
$app->post('refreshAccessToken', [
  'uses' => 'AuthController@refreshToken'
]);
$app->post('checkAccessTokenValidity', [
  'uses' => 'AuthController@checkTokenValidity'
]);

//////////////////////
// USERS MANAGEMENT //
//////////////////////
$app->get('users', [
  'middleware' => 'auth:true',
  'uses' => 'UsersController@showAllUsers'
]);
$app->get('user/{id}', [
  'middleware' => 'auth:true',
  'uses' => 'UsersController@showOneUser'
]);
$app->get('user', [
  'middleware' => 'auth',
  'uses' => 'UsersController@showAuthenticatedUser'
]);
$app->post('users', [
  'uses' => 'UsersController@addUser'
]);
$app->put('user/{id}', [
  'middleware' => 'auth',
  'uses' => 'UsersController@editUser'
]);