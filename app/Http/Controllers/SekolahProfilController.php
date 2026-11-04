<?php

namespace App\Http\Controllers;

use App\Models\SekolahDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravolt\Indonesia\Models\Province;
use App\Models\Semester;

class SekolahProfilController extends Controller
{
  public function edit()
  {
    $user = Auth::user();
    $sekolah = $user->ptk->sekolah; // pastikan relasi ini ada di model Ptk

    $detail = $sekolah->sekolahDetail()
      ->where('semester_id', Semester::smtId())
      ->first();

    $provinces = Province::all();

    return view('content.sekolah.profil', compact('sekolah', 'detail', 'provinces'));
  }

  public function update(Request $request)
  {
    $user = Auth::user();
    $sekolah = $user->ptk->sekolah;

    $validator = Validator::make($request->all(), [
      'nama_sekolah'     => 'required|string|max:255',
      'province'         => 'required',
      'regency'          => 'required',
      'district'         => 'required',
      'village'          => 'required',
      'alamat_sekolah'   => 'required|string',
      'kepala_sekolah'   => 'required|string|max:255',
      'nip'              => 'nullable|string|size:18',
      'no_telp'          => 'nullable|string|max:20',
      'email'            => 'nullable|email|max:255',
      'libur_mingguan'   => 'nullable|array',
      'libur_mingguan.*' => 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $semesterId = Semester::sessionSemesterId(); // boleh tegas di sini, ini operasi simpan

    $dataDetail = [
      'nama_sekolah'    => $request->nama_sekolah,
      'province_id'     => $request->province,
      'regency_id'      => $request->regency,
      'district_id'     => $request->district,
      'village_id'      => $request->village,
      'alamat_sekolah'  => $request->alamat_sekolah,
      'kepala_sekolah'  => $request->kepala_sekolah,
      'nip'             => $request->nip,
      'no_telp'         => $request->no_telp,
      'email'           => $request->email,
      'libur_mingguan'  => $request->libur_mingguan ?? [],
    ];

    $detail = $sekolah->sekolahDetail()->where('semester_id', $semesterId)->first();

    if ($detail) {
      $detail->update($dataDetail);
    } else {
      $dataDetail['semester_id'] = $semesterId;
      $sekolah->sekolahDetail()->create($dataDetail);
    }

    return response()->json(['message' => 'Data sekolah berhasil diperbarui.']);
  }
}
