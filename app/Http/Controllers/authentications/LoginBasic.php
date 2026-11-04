<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\Request;

class LoginBasic extends Controller
{
  public function index()
  {
    $pageConfigs = ['myLayout' => 'blank'];
    $semesters = Semester::orderBy('id', 'desc')->get();
    return view('content.authentications.auth-login-basic', ['pageConfigs' => $pageConfigs, 'semesters'   => $semesters,]);
  }
}
