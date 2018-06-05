<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class SystemController extends Controller
{
  private $request;
  private $user_id;

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
  }

  /**
   * Insert and save weather data sent by the system
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function addData()
  {
    $this->validate($this->request, [
      'serial_key' => 'required',
      'data' => 'required|array'
    ]);
    $params = $this->request->only('serial_key', 'data');

    $serialKey = $params['serial_key'];
    $data = $params['data'];

    if ($results = DB::select("SELECT id FROM systems WHERE serial_key = :serial_key", ['serial_key' => $serialKey])) {
      $inserted = DB::insert("INSERT INTO data SET system_id = :system_id, data = :data",
        [
          'system_id' => $results[0]->id,
          'data' => json_encode($data)
        ]);

      if ($inserted) {
        // All is good
        return response('')->setStatusCode(200);
      }
    }

    // Insert or select failed
    return response('')->setStatusCode(500);
  }


  /**
   * Give weather data sent by the system
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getData()
  {
    if ($results = DB::select("SELECT id FROM systems WHERE owner_id = :user_id", ['user_id' => $this->user_id])) {
      $data = DB::select("SELECT data, checked_at FROM data WHERE system_id = :system_id ORDER BY checked_at DESC", ['system_id' => $results[0]->id]);
      $date1Day = date_format(date_modify(date_create(date('Y-m-d H:i:s')), "-1 day"), 'Y-m-d H:i:s');
      $dataDay = DB::select("SELECT data FROM data WHERE system_id = :system_id && checked_at > '$date1Day'", ['system_id' => $results[0]->id]);

      $rainfallSum = 0;
      foreach ($dataDay as $value) {
        $rainfallSum += json_decode($value->data, true)['rainfall'];
      }

      if ($data && $dataDay) {
        $dataArr = json_decode($data[0]->data, true);
        // All is good
        return response()->json([
          'data' => [
            'temperature' => [
              'last' => $dataArr['temperature']
            ],
            'humidity' => [
              'last' => $dataArr['humidity']
            ],
            'rainfall' => [
              'last' => $dataArr['rainfall'],
              'sum' => $rainfallSum ? $rainfallSum : '-'
            ],
          ],
          'checked_at' => $data[0]->checked_at
        ]);
      }
    }

    // Select failed
    return response('')->setStatusCode(500);
  }


  /**
   * Give raw weather data sent by the system
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getRawData()
  {
    $params = $this->request->only('since_days');

    if ($results = DB::select("SELECT id FROM systems WHERE owner_id = :user_id", ['user_id' => $this->user_id])) {
      $to_date = date_format(date_modify(date_create(date('Y-m-d H:i:s')), "- " . $params["since_days"] . " day"), 'Y-m-d H:i:s');
      $data = DB::select("SELECT data, checked_at FROM data WHERE system_id = :system_id && checked_at > '$to_date' ORDER BY checked_at", ['system_id' => $results[0]->id]);

      if ($data) {
        $dataArr = [];
        foreach ($data as $value) {
          $dataArr[]['data'] = json_decode($value->data);
          $dataArr[]['checked_at'] = $value->checked_at;
        }

        // All is good
        return response()->json($dataArr);
      }
    }


    // Select failed
    return response('')->setStatusCode(500);
  }
}
