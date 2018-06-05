<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
  private $request;

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

    $this->request = $request;
    $this->user_id = $request->get('user_id');
    $this->is_admin = $request->get('is_admin');
  }

  /**
   * Shows system schedule in JSON format
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getSchedule()
  {
    if ($results = DB::select("SELECT schedule FROM systems WHERE owner_id = '$this->user_id'")) {
      return response()->json(json_decode($results[0]->schedule)); // Success
    }

    // No system
    return response()->json([
      'message' => 'There is no system associated to that api key',
    ])->setStatusCode(404);
  }

  /**
   * Edit system schedule
   *
   *
   * @return \Laravel\Lumen\Http\ResponseFactory|\Symfony\Component\HttpFoundation\Response
   */
  public function editSchedule()
  {
    $this->validate($this->request, [
      'end_flow' => 'required|min:0',
      'volume' => 'required|min:0',
      'duration' => 'required',
      'surface' => 'required|min:0',
      'days' => 'array',
      'moment' => 'required|in:morning,afternoon,evening'
    ]);
    $params = $this->request->only('end_flow', 'volume', 'duration', 'surface', 'days', 'moment');

    $data = [
      'end_flow' => $params['end_flow'],
      'volume' => $params['volume'],
      'duration' => $params['duration'],
      'surface' => $params['surface'],
      'days' => $params['days'],
      'moment' => $params['moment']
    ];
    $schedule = json_encode($data);

    if (DB::update("UPDATE systems SET schedule = '$schedule' WHERE owner_id = '$this->user_id'")) {
      // All is good
      return response('');
    }

    // Update failed
    return response('')->setStatusCode(500);
  }

}
