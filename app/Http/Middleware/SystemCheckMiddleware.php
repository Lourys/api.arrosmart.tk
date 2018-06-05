<?php

namespace App\Http\Middleware;

use DB;
use Closure;

class SystemCheckMiddleware
{
  public $attributes;

  /**
   * Handle an incoming request and check if it's a authenticated system
   *
   * @param  \Illuminate\Http\Request $request
   * @param  \Closure $next
   *
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    $params = $request->only('auth');
    if ($params['auth'] === '11k&IVElLzU63DTZ71?1mH$wKl3y;t64_VK61R*w:>91bJBL_!nnNd17T|12Gs9O1cIaE3D6KmNo_KMtY4f\'b1S82') {
      return $next($request);
    }

    abort(403, 'Unauthorized action.');
  }
}
