<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class AuthController extends Controller
{
  private $request;

  /**
   * UsersController constructor.
   *
   * @param Request $request
   */
  public function __construct(Request $request)
  {
    $this->request = $request;
  }

  /**
   * Authenticates an user by his credentials and responds with a token
   *
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function createToken()
  {
    $params = $this->request->only('email', 'password');
    $email = $params['email'];
    $pass = $params['password'];

    if ($results = DB::select("SELECT id, password FROM users WHERE email = :email", ['email' => $email])) {
      if (password_verify($pass, $results[0]->password)) {
        $inserted = DB::insert("INSERT INTO tokens SET token = :token, user_agent = :user_agent, ip_address = :ip_address, user_id = :user_id",
          [
            'token' => $this->generateToken(),
            'user_agent' => $this->request->header('User-Agent'),
            'ip_address' => $this->request->ip(),
            'user_id' => $results[0]->id
          ]);

        if ($inserted) {
          $rowId = DB::connection()->getPdo()->lastInsertId();
          $results = DB::select("SELECT token FROM tokens WHERE id = :id", ['id' => $rowId]);

          // All is good, returning token
          return response()->json([
            'token' => $results[0]->token
          ]);
        }

        // Insert failed
        return response()->setStatusCode(500);
      }
    }

    // User not found
    return response()->json([
      'message' => 'Email or password incorrect',
    ])->setStatusCode(401);
  }

  /**
   * Refreshes a given token and and responds with the new token
   *
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function refreshToken()
  {
    $params = $this->request->only('token');
    $token = $params['token'];

    if ($results = DB::select("SELECT id FROM tokens WHERE token = :token", ['token' => $token])) {
      $token = $this->generateToken();
      $updated = DB::update("UPDATE tokens SET token = :token WHERE id = :id",
        [
          'token' => $token,
          'id' => $results[0]->id
        ]);

      if ($updated) {
        // All is good, returning token
        return response()->json([
          'token' => $token
        ]);
      }

      // Update failed
      return response()->setStatusCode(500);
    }

    // Token not found
    return response()->json([
      'message' => 'Token not found',
    ])->setStatusCode(404);
  }

  public function checkTokenValidity()
  {
    $params = $this->request->only('token');
    $token = $params['token'];

    if ($results = DB::select("SELECT id FROM tokens WHERE token = :token", ['token' => $token])) {
        // All is good, returning token
        return response()->json([
          'valid' => true
        ]);
    }

    // Token not found
    return response()->json([
      'message' => 'Token not found',
    ])->setStatusCode(404);
  }

  private function generateToken()
  {
    return bin2hex(random_bytes(32));
  }
}
