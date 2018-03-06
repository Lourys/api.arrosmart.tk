<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class UsersController extends Controller
{
  private $request;

  private $columns = [];

  /**
   * UsersController constructor.
   *
   * @param Request $request
   */
  public function __construct(Request $request)
  {
    // Get columns in Users table
    $this->columns = DB::select("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='arrosmart' AND `TABLE_NAME`='users'");
    $this->columns = array_column(collect($this->columns)->map(function ($x) { // Converting objects into the array to array
      return (array)$x;
    })->toArray(), 'COLUMN_NAME');

    $this->request = $request;
  }

  /**
   * Shows all users data in JSON format
   *
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function showAllUsers()
  {
    $params = $this->request->all();
    $fields = isset($params['fields']) ? $params['fields'] : '*';


    if ($results = DB::select("SELECT $fields FROM users")) {
      return response()->json($results); // Success
    }

    // User not found
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

    $params = $this->request->all();
    $fields = isset($params['fields']) ? $params['fields'] : '*';

    if ($results = DB::select("SELECT $fields FROM users WHERE id = $id")) {
      return response()->json($results[0]); // Success
    }

    return response()->json([
      'message' => 'The user requested does not exist',
    ])->setStatusCode(404);
  }


  /**
   * Creates new user
   *
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function createUser()
  {
    $results = DB::select("SELECT $this->fields FROM users WHERE id = $id");

    return response()->json($results[0]);
  }
}
