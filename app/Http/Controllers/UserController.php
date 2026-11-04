<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
  // Menampilkan halaman profil
  public function index()
  {
    $user = auth()->user()->load('Ptk');
    return view('content.user.profil', compact('user'));
  }

  // Memperbarui data profil (User & PTK)
  public function update(Request $request)
  {
    $user = auth()->user();

    $request->validate([
      'name' => 'required|string|max:255',
      'user_name' => 'required|string|max:255|unique:users,user_name,' . $user->id,
      'email' => 'required|email|max:255|unique:users,email,' . $user->id,
      'nama_ptk' => 'required|string|max:255',
      'nip' => 'nullable|string|max:50',
      'jenis_kelamin' => 'required|in:L,P',
      'jabatan' => 'nullable|string|max:100',
      'jenis_kepegawaian' => 'nullable|string|max:100',
      'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Maksimal 2MB
    ]);

    // Update tabel users
    $user->update([
      'name' => $request->name,
      'user_name' => $request->user_name,
      'email' => $request->email,
    ]);

    // Cek jika user memiliki relasi PTK
    if ($user->Ptk) {
      $ptkData = $request->only(['nama_ptk', 'nip', 'jenis_kelamin', 'jabatan', 'jenis_kepegawaian']);

      // Handle upload foto
      if ($request->hasFile('photo')) {
        // Hapus foto lama jika ada
        if ($user->Ptk->photo && Storage::disk('public')->exists($user->Ptk->photo)) {
          Storage::disk('public')->delete($user->Ptk->photo);
        }
        // Simpan foto baru
        $ptkData['photo'] = $request->file('photo')->store('ptk_photos', 'public');
      }

      $user->Ptk()->update($ptkData);
    }

    return back()->with('success', 'Profil berhasil diperbarui.');
  }

  // Memperbarui password
  public function updatePassword(Request $request)
  {
    $request->validate([
      'current_password' => 'required|current_password',
      'password' => ['required', 'confirmed', Password::defaults()],
    ]);

    $request->user()->update([
      'password' => Hash::make($request->password),
    ]);

    return back()->with('success', 'Password berhasil diubah.');
  }
}
