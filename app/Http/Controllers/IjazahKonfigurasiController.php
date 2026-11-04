<?php

namespace App\Http\Controllers;

use App\Models\IjazahKonfigurasi;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IjazahKonfigurasiController extends Controller
{
    private function getContext(): array
    {
        $sekolahId = Auth::user()->ptk?->sekolah_id;
        abort_if(!$sekolahId, 403, 'Akun Anda belum terhubung ke sekolah.');
        $semesterId = (int) session('semester_id');
        $semester   = Semester::findOrFail($semesterId);

        return [$sekolahId, $semesterId, $semester];
    }

    public function edit()
    {
        [$sekolahId, $semesterId, $semester] = $this->getContext();

        $konfigurasi = IjazahKonfigurasi::firstOrDefault($sekolahId, $semesterId);

        return view('content.ijazah.konfigurasi', compact('konfigurasi', 'semester'));
    }

    public function update(Request $request)
    {
        [$sekolahId, $semesterId] = $this->getContext();

        $konfigurasi = IjazahKonfigurasi::firstOrDefault($sekolahId, $semesterId);

        // Mode input terkunci jika sudah ada SKL, tapi tempat/tanggal tetap bisa diubah
        if ($konfigurasi->is_terkunci && $request->has('mode_input')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Mode input sudah terkunci karena sudah ada data SKL yang dibuat.',
                ], 422);
            }
            return back()->withErrors(['mode_input' => 'Mode input sudah terkunci.']);
        }

        $request->validate([
            'mode_input'  => 'sometimes|required|in:jadi,manual',
            'tempat_skl'  => 'nullable|string|max:100',
            'tanggal_skl' => 'nullable|date',
        ]);

        $updateData = array_filter([
            'tempat_skl'  => $request->tempat_skl,
            'tanggal_skl' => $request->tanggal_skl,
        ], fn($v) => $v !== null);

        if (!$konfigurasi->is_terkunci && $request->has('mode_input')) {
            $updateData['mode_input'] = $request->mode_input;
        }

        $konfigurasi->update($updateData);

        if ($request->expectsJson()) {
            return response()->json([
                'message'     => 'Konfigurasi berhasil disimpan.',
                'konfigurasi' => $konfigurasi->fresh(),
            ]);
        }

        return back()->with('success', 'Konfigurasi berhasil disimpan.');
    }
}
