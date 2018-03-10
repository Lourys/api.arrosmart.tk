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

$app->get('/', function () use ($app) {
  abort(403, 'Unauthorized action.');
});

$app->post('getAccessToken', [
  'uses' => 'AuthController@createToken'
]);
$app->post('refreshAccessToken', [
  'uses' => 'AuthController@refreshToken'
]);
$app->post('checkAccessTokenValidity', [
  'uses' => 'AuthController@checkTokenValidity'
]);

$app->get('users', [
  'uses' => 'UsersController@showAllUsers'
]);
$app->get('user/{id}', [
  'uses' => 'UsersController@showOneUser'
]);
$app->post('users', [
  'uses' => 'UsersController@addUser'
]);
