<?php

namespace App\Http\Middleware;

use DB;
use Closure;

class AuthCheckMiddleware
{
  public $attributes;

  /**
   * Handle an incoming request and check if user is authenticated
   *
   * @param  \Illuminate\Http\Request $request
   * @param  \Closure $next
   * @param bool $admin
   *
   * @return mixed
   */
  public function handle($request, Closure $next, $admin = false)
  {
    $params = $request->only('token');
    if (isset($params['token']) && $result = DB::select("SELECT t.user_id, u.is_admin FROM tokens t, users u WHERE t.token = :token && u.id = t.user_id", ['token' => $params['token']])) {
      if (($admin && $result[0]->is_admin) || !$admin) {
        $request->attributes->add(['user_id' => $result[0]->user_id]);
        $request->attributes->add(['is_admin' => $result[0]->is_admin]);
        return $next($request);
      }

      abort(403, 'Unauthorized action.');
    }

    abort(403, 'Unauthorized action.');
  }
}
