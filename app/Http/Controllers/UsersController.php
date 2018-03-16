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
  public function showOneUser($id)
  {
    // Simple check
    if (!is_numeric($id)) {
      return response()->json([
        'message' => 'Parameter(s) is/are not in the correct format',
        'errors' => [
          [
            'message' => 'Oops! The parameter must be numeric',
            'parameter' => 'id'
          ],
        ]
      ])->setStatusCode(400);
    }

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
    $params = $this->request->all();


    $inserted = DB::insert("INSERT INTO users SET email = :email, password = :password, first_name = :first_name, last_name = :last_name, serial_key = :serial_key",
      [
        'email' => $params['email'],
        'password' => password_hash($params['password'], PASSWORD_BCRYPT),
        'first_name' => $params['first_name'],
        'last_name' => strtoupper($params['last_name']),
        'serial_key' => $params['serial_key']
      ]);

    if ($inserted) {
      // All is good
      return response('');
    }

    // Insert failed
    return response('')->setStatusCode(500);
  }

  public function editUser($id = null)
  {
    if ((is_numeric($id) && $this->is_admin) || is_null($id)) {
      $id = is_null($id) ? $this->user_id : $id;

      $this->validate($this->request, [
        'first_name' => 'required',
        'last_name' => 'required',
        'department' => 'required|max:2',
        'serial_key' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'confirmed|min:8'
      ]);
      $params = $this->request->all();

      $fields = '';
      foreach ($params as $param) {
        $fields .= ',' . $param . ' = :' . $param;
      }
      substr($fields, 1);

      $updated = DB::insert("UPDATE users SET $fields WHERE id = $id",
        [
          'email' => $params['email'],
          'password' => password_hash($params['password'], PASSWORD_BCRYPT), ////////// A FAIRE
          'first_name' => $params['first_name'],
          'last_name' => strtoupper($params['last_name']),
          'department' => $params['department']
        ]);

      if ($updated) {
        // All is good
        return response('');
      }

      // Update failed
      return response('')->setStatusCode(500);
    }

    return abort(403, 'Unauthorized action.');
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
