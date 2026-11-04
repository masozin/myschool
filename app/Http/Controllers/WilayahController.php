<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;

class WilayahController extends Controller
{
  public function getProvinsi()
  {
    $data = Province::select('code', 'name')->get();

    return response()->json($data);
  }

  public function getKabupaten($province_code)
  {
    $data = City::where('province_code', $province_code)
      ->select('code', 'name')
      ->get();

    return response()->json($data);
  }

  public function getKecamatan($city_code)
  {
    $data = District::where('city_code', $city_code)
      ->select('code', 'name')
      ->get();

    return response()->json($data);
  }

  public function getDesa($district_code)
  {
    $data = Village::where('district_code', $district_code)
      ->select('code', 'name')
      ->get();

    return response()->json($data);
  }
}
