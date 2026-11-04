<?php

namespace App\Http\Controllers;

use App\Models\Semester;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SemesterController extends Controller
{
  public function index()
  {
    return view('content.semester.index');
  }

  public function table()
  {
    $semester = Semester::orderBy('kode_semester', 'desc')->get();
    return response()->json($semester);
  }

  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'kode_semester'   => 'required|unique:tabel_semester,kode_semester',
      'semester'        => 'required|string|max:255',
      'tahun_pelajaran' => 'required',
      'is_active'       => 'boolean',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
      DB::beginTransaction();

      $isActive = $request->boolean('is_active');

      // Opsional: Jika semester baru aktif, set yang lain jadi non-aktif
      if ($isActive) {
        Semester::where('is_active', 1)->update(['is_active' => 0]);
      }

      Semester::create(array_merge(
        $request->only(['kode_semester', 'semester', 'tahun_pelajaran', 'is_active']),
        ['is_active' => $isActive]
      ));

      DB::commit();
      return response()->json(['message' => 'Data semester berhasil ditambahkan!'], 201);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['message' => 'Gagal simpan data.', 'error' => $e->getMessage()], 500);
    }
  }

  public function update(Request $request, $id)
  {
    $semester = Semester::findOrFail($id);

    $validator = Validator::make($request->all(), [
      'kode_semester'   => 'required|unique:tabel_semester,kode_semester,' . $id,
      'semester'        => 'required|string|max:255',
      'tahun_pelajaran' => 'required',
      'is_active'       => 'boolean',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
      DB::beginTransaction();

      $isActive = $request->boolean('is_active');

      // Jika update ini mengaktifkan semester, matikan semester lainnya
      if ($isActive) {
        Semester::where('id', '!=', $id)->where('is_active', 1)->update(['is_active' => 0]);
      }

      $semester->update(array_merge(
        $request->only(['kode_semester', 'semester', 'tahun_pelajaran']),
        ['is_active' => $isActive]
      ));

      DB::commit();
      return response()->json(['message' => 'Data semester berhasil diperbarui!']);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['message' => 'Gagal memperbarui data.', 'error' => $e->getMessage()], 500);
    }
  }
}
