<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class UsersController extends Controller
{
  private $request;

  private $columns = [];
  private $user_id;
  private $is_admin;

  /**
   * UsersController constructor.
   *
   * @param Request $request
   */
  public function __construct(Request $request)
  {
    parent::__construct();

    // Get columns in Users table
    $this->columns = DB::select("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='arrosmart' AND `TABLE_NAME`='users'");
    $this->columns = array_column(collect($this->columns)->map(function ($x) { // Converting objects into the array to array
      return (array)$x;
    })->toArray(), 'COLUMN_NAME');

    $this->request = $request;
    $this->user_id = $request->get('user_id');
    $this->is_admin = $request->get('is_admin');
  }

  /**
   * Shows all users data in JSON format
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function showAllUsers()
  {
    $params = $this->request->all();
    $fields = isset($params['fields']) ? $this->getFields($params['fields'], $this->columns) : '*';


    if ($results = DB::select("SELECT $fields FROM users")) {
      return response()->json($results); // Success
    }

    // No user
    return response()->json([
      'message' => 'There is no user',
    ])->setStatusCode(404);
  }

  /**
   * Shows one user data in JSON format
   *
   * @param $id int
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function showOneUser($id = null)
  {
    $id = is_null($id) ? $this->user_id : $id;

    $params = $this->request->only('fields');
    $fields = isset($params['fields']) ? $this->getFields($params['fields'], $this->columns) : '*';

    if ($results = DB::select("SELECT $fields FROM users WHERE id = :id", ['id' => $id])) {
      return response()->json($results[0]); // Success
    }

    // User not found
    return response()->json([
      'message' => 'The user requested does not exist',
    ])->setStatusCode(404);
  }

  /**
   * Shows current user data's in JSON format
   *
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function showAuthenticatedUser()
  {
    $params = $this->request->only('token', 'fields');
    $fields = isset($params['fields']) ? $this->getFields($params['fields'], $this->columns) : '*';

    if ($results = DB::select("SELECT $fields FROM users WHERE id = :id", ['id' => $this->user_id])) {
      return response()->json($results[0]); // Success
    }

    // User not found
    return response()->json([
      'message' => 'The user requested does not exist',
    ])->setStatusCode(404);
  }

  /**
   * Creates a new user
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function addUser()
  {
    $this->validate($this->request, [
      'first_name' => 'required',
      'last_name' => 'required',
      'serial_key' => 'required',
      'email' => 'required|email|unique:users',
      'password' => 'required|confirmed|min:8'
    ]);
    $params = $this->request->only('first_name', 'last_name', 'serial_key', 'email', 'password');

    // Checks if serial key is available
    $system = DB::select("SELECT id FROM systems WHERE serial_key = :serial_key && owner_id IS NULL", ['serial_key' => $params['serial_key']]);
    if (!$system) {
      return response()->json(['serial_key' => ['Le numéro de série que vous avez entré est invalide.']])->setStatusCode(422);
    }

    $inserted = DB::insert("INSERT INTO users SET email = :email, password = :password, first_name = :first_name, last_name = :last_name",
      [
        'email' => $params['email'],
        'password' => password_hash($params['password'], PASSWORD_BCRYPT),
        'first_name' => $params['first_name'],
        'last_name' => strtoupper($params['last_name'])
      ]);

    if ($inserted) {
      $user_id = DB::connection()->getPdo()->lastInsertId();
      DB::update("UPDATE systems SET owner_id = :owner_id WHERE serial_key = :serial_key", ['owner_id' => $user_id, 'serial_key' => $params['serial_key']]);
      // All is good
      return response('');
    }

    // Insert failed
    return response('')->setStatusCode(500);
  }

  /**
   * Edit an user by his ID
   *
   * @param null|int $id
   *
   * @return \Laravel\Lumen\Http\ResponseFactory|\Symfony\Component\HttpFoundation\Response
   */
  public function editUser($id = null)
  {
    $id = is_null($id) ? $this->user_id : $id;

    $this->validate($this->request, [
      'first_name' => 'required',
      'last_name' => 'required',
      'department' => 'required|max:2',
      'email' => 'required|email|unique:users,email,' . $id,
      'password' => 'confirmed|min:8'
    ]);
    $params = $this->request->only('first_name', 'last_name', 'department', 'email', 'password');

    $fields = '';
    foreach ($params as $key => $param) {
      if (isset($key) && $param != '') {
        $fields .= $key . ' = :' . $key . ', ';
      }
    }
    $fields = substr($fields, 0, -2);

    $data = [
      'email' => $params['email'],
      'first_name' => $params['first_name'],
      'last_name' => strtoupper($params['last_name']),
      'department' => $params['department']
    ];
    if (isset($params['password']) && $params['password'] != '') {
      $data['password'] = password_hash($params['password'], PASSWORD_BCRYPT);
    }

    if (DB::update("UPDATE users SET $fields WHERE id = $id", $data)) {
      // All is good
      return response('');
    }

    // Update failed
    return response('')->setStatusCode(500);
  }


  /**
   * Edit user's settings
   *
   * @param null|int $user_id
   *
   * @return \Laravel\Lumen\Http\ResponseFactory|\Symfony\Component\HttpFoundation\Response
   */
  public function editUserSettings($user_id = null)
  {
    $user_id = is_null($user_id) ? $this->user_id : $user_id;

    $this->validate($this->request, [
      'water_price' => 'required|min:0'
    ]);
    $params = $this->request->only('water_price');

    $data = [
      'water_price' => $params['water_price']
    ];

    if (DB::update("UPDATE users SET settings = :settings WHERE id = $user_id", ['settings' => json_encode($data)])) {
      // All is good
      return response('');
    }

    // Update failed
    return response('')->setStatusCode(500);
  }


  private function getFields($fields, $columns)
  {
    if (isset($fields)) {
      foreach (explode(',', $fields) as $field) {
        $field = strtolower($field);
        if (in_array($field, $columns)) {
          $endFields[] = $field;
        }
      }

      return implode(', ', $endFields);
    }

    return false;
  }
}
