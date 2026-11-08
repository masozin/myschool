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

  /**
   * Buka / terapkan kembali kunci semester (grup route ini
   * khusus Admin). Semester non-aktif terkunci untuk perubahan
   * data di seluruh aplikasi (CheckSemesterLock); tombol ini
   * membukanya bila memang diperlukan.
   */
  public function kunci(Request $request, $id)
  {
    $semester = Semester::findOrFail($id);
    $semester->update(['kunci_dibuka' => $request->input('aksi') === 'buka']);

    return response()->json([
      'message' => $semester->kunci_dibuka
        ? 'Kunci semester dibuka. Perubahan data kembali diizinkan.'
        : 'Kunci semester diterapkan kembali.',
    ]);
  }

  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'kode_semester'   => 'required|unique:tabel_semester,kode_semester',
      'semester'        => 'required|string|in:Ganjil,Genap',
      'tahun_pelajaran' => 'required|regex:/^\d{4}\/\d{4}$/',
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
      'semester'        => 'required|string|in:Ganjil,Genap',
      'tahun_pelajaran' => 'required|regex:/^\d{4}\/\d{4}$/',
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
  public function destroy($id)
  {
    try {
      $semester = Semester::findOrFail($id);

      // Semester yang sedang aktif tidak boleh dihapus
      if ($semester->is_active) {
        return response()->json([
          'message' => 'Semester aktif tidak bisa dihapus. Aktifkan semester lain terlebih dahulu.'
        ], 422);
      }

      // Cek relasi: semester yang sudah dipakai tidak boleh dihapus
      $hasRelations = $semester->rombel()->exists()
        || $semester->sekolahDetail()->exists()
        || $semester->riwayatSiswa()->exists();

      if ($hasRelations) {
        return response()->json([
          'message' => 'Data semester tidak bisa dihapus karena sudah dipakai oleh data rombel, sekolah, atau siswa.'
        ], 422);
      }

      // Soft delete (model memakai trait SoftDeletes)
      $semester->delete();

      return response()->json(['message' => 'Data semester berhasil dihapus!']);
    } catch (\Exception $e) {
      return response()->json(['message' => 'Gagal menghapus data.', 'error' => $e->getMessage()], 500);
    }
  }
}
