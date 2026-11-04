# Dokumentasi Sistem Manajemen Sekolah

> Terakhir diperbarui: April 2026

---

## DAFTAR ISI

1. [Arsitektur Sistem](#1-arsitektur-sistem)
2. [Autentikasi & Session Semester](#2-autentikasi--session-semester)
3. [Middleware](#3-middleware)
4. [Model & Relasi](#4-model--relasi)
5. [Controller](#5-controller)
6. [Policy & Otorisasi](#6-policy--otorisasi)
7. [Menu Dinamis](#7-menu-dinamis)
8. [View & Blade](#8-view--blade)
9. [Semester Lock (Proteksi Data)](#9-semester-lock-proteksi-data)
10. [Route](#10-route)
11. [Dependensi Antar Komponen](#11-dependensi-antar-komponen)
12. [Hal yang Perlu Diperhatikan](#12-hal-yang-perlu-diperhatikan)

---

## 1. ARSITEKTUR SISTEM

```
Login (pilih semester)
    ↓
LoginResponse → simpan semester_id ke session
    ↓
SemesterSessionMiddleware → safety net jika session expired
    ↓
User browsing → semua data terfilter berdasarkan session semester_id
    ↓
AppServiceProvider → kirim $nama_semester, $isEditable ke semua view
    ↓
CheckSemesterLock Middleware → blokir POST/PUT/DELETE jika semester tidak aktif
    ↓
Logout → LogoutResponse → redirect ke /login
```

---

## 2. AUTENTIKASI & SESSION SEMESTER

### File Terkait

| File                     | Lokasi                                  | Fungsi                                   |
| ------------------------ | --------------------------------------- | ---------------------------------------- |
| `LoginBasic.php`         | `app/Http/Controllers/authentications/` | Kirim `$semesters` ke view login         |
| `LoginResponse.php`      | `app/Http/Responses/`                   | Simpan semester ke session saat login    |
| `LogoutResponse.php`     | `app/Http/Responses/`                   | Redirect ke `/login` setelah logout      |
| `AppServiceProvider.php` | `app/Providers/`                        | Daftarkan LoginResponse & LogoutResponse |

### Alur Login

1. User buka `/login`
2. Dropdown semester tampil (default = semester `is_active = 1`)
3. User pilih semester → submit
4. Fortify autentikasi → `LoginResponse::toResponse()`
5. `semester_id` yang dipilih disimpan ke session
6. Redirect ke dashboard

### `LoginResponse.php`

```php
public function toResponse($request)
{
    if ($request->filled('semester_id')) {
        $semester = Semester::find($request->integer('semester_id'));
        if ($semester) {
            session(['semester_id' => $semester->id]);
        }
    }

    // Fallback jika tidak ada semester dipilih
    if (!session()->has('semester_id')) {
        $semester = Semester::where('is_active', 1)->latest()->first()
            ?? Semester::latest()->first();
        if ($semester) session(['semester_id' => $semester->id]);
    }

    return redirect()->intended(config('fortify.home'));
}
```

### `AppServiceProvider::register()`

```php
// Dua singleton yang wajib ada
$this->app->singleton(LoginResponseContract::class, LoginResponse::class);
$this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
```

### `AppServiceProvider::boot()` — View Composer

```php
// 1. Kirim $semesters ke halaman login
View::composer('auth.login', function ($view) {
    $view->with('semesters', Semester::orderBy('id', 'desc')->get());
});

// 2. Kirim $nama_semester dan $isEditable ke semua view
View::composer('*', function ($view) {
    if (Auth::check()) {
        $semesterId = session('semester_id');
        $semester   = Semester::find($semesterId);
        $isEditable = Auth::user()->hasRole('admin')
            || ($semester && $semester->is_active == 1);

        $view->with([
            'nama_semester' => $semester->nama_semester ?? '-',
            'isEditable'    => $isEditable,
        ]);
    }
});
```

> ⚠️ **PERHATIAN:** `AppServiceProvider` dan `MenuServiceProvider` keduanya pakai
> `View::composer('*', ...)`. Pastikan tidak saling menimpa variabel.
> Urutan di `bootstrap/providers.php`: `MenuServiceProvider` dulu, baru `AppServiceProvider`.

---

## 3. MIDDLEWARE

### `SemesterSessionMiddleware`

**Lokasi:** `app/Http/Middleware/SemesterSessionMiddleware.php`
**Fungsi:** Safety net — set ulang semester jika session hilang (expired)
**Kapan jalan:** Setiap request dari user yang sudah login

```php
if (Auth::check() && !Session::has('semester_id')) {
    $semester = Semester::where('is_active', 1)->latest()->first()
        ?? Semester::latest()->first();
    if ($semester) Session::put('semester_id', $semester->id);
}
```

### `CheckSemesterLock`

**Lokasi:** `app/Http/Middleware/CheckSemesterLock.php`
**Fungsi:** Blokir aksi tulis (POST/PUT/DELETE) jika semester tidak aktif
**Pengecualian:** Admin selalu diizinkan, GET selalu diizinkan

```
GET request    → lewat bebas
Admin          → lewat bebas
Semester aktif → lewat
Semester lama  → 403 JSON atau redirect back
```

**Diterapkan di route:**

```php
Route::middleware(['auth', 'role:...', 'semester.lock'])->group(function () {
    Route::post('/rombel/store', ...);
    Route::post('/rombel/update/{id}', ...);
    Route::delete('/rombel/delete/{id}', ...);
    Route::post('/anggota-rombel/store', ...);
    Route::delete('/anggota-rombel/destroy/{id}', ...);
    Route::post('/siswa/store', ...);
    Route::put('/siswa/update/{id}', ...);
});
```

---

## 4. MODEL & RELASI

### `Siswa`

| Relasi                | Tipe          | Keterangan                            |
| --------------------- | ------------- | ------------------------------------- |
| `rombelAnggota()`     | HasMany       | Riwayat semua rombel siswa            |
| `rombels()`           | BelongsToMany | Via `tabel_anggota_rombel`            |
| `siswaRegistrasi()`   | HasMany       | Semua registrasi                      |
| `registrasiTerbaru()` | HasOne        | `latestOfMany()` — registrasi terbaru |

> ⚠️ `registrasiTerbaru()` sudah pakai `latestOfMany()` — **tidak perlu** subquery `MAX(id)` manual

### `RombelAnggota`

| Kolom         | Keterangan                               |
| ------------- | ---------------------------------------- |
| `siswa_id`    | FK ke tabel_siswa                        |
| `rombel_id`   | FK ke tabel_rombel                       |
| `semester_id` | FK ke tabel_semester (disimpan langsung) |
| `start_date`  | Tanggal masuk rombel                     |
| `end_date`    | Tanggal keluar (null = masih aktif)      |

> ⚠️ `end_date = null` berarti **aktif**. Jangan gunakan `delete()` untuk keluarkan siswa,
> gunakan `update(['end_date' => now()])` agar history terjaga.

### `Sekolah`

| Relasi                | Keterangan                         |
| --------------------- | ---------------------------------- |
| `detailTerbaru()`     | HasOne latestOfMany — untuk navbar |
| `detailPerSemester()` | HasOne where semester_id = session |

### `User`

> ⚠️ Relasi `ptk()` dan `siswa()` harus `public`, bukan `protected`

```php
public function ptk(): HasOne { ... }
public function siswa(): HasOne { ... }
```

---

## 5. CONTROLLER

### `RombelAnggotaController`

#### `RombelAnggota($rombel_id)`

- Tampilkan anggota aktif (`whereNull('end_date')`)
- Eager load: `siswa.siswaRegistrasi`

#### `listSiswaTersedia($rombel_id)`

- Ketentuan A: Registrasi aktif & sekolah sama
- Ketentuan B: Belum punya rombel di semester ini (`whereNull('end_date')`)
- Ketentuan C: Logika tingkat (ganjil/genap)
  - **Ganjil:** Siswa baru ATAU naik/tinggal kelas (filter `tahun_pelajaran`)
  - **Genap:** Ada di semester ganjil yang sama ATAU siswa baru/mutasi
- ⚠️ Proteksi `is_active` **dihapus** dari sini, dipindah ke `store()`

#### `store(Request $request)`

- Terima array `siswa_ids`
- Cek duplikasi: `whereNull('end_date')` per semester
- Simpan `semester_id` langsung ke `tabel_anggota_rombel`
- ✅ Proteksi `is_active` ada di sini

#### `destroy($id)`

- **Tidak** `delete()` — gunakan `update(['end_date' => now()])`
- Proteksi: semester harus aktif
- Proteksi: `end_date` harus null (masih aktif)

#### `riwayatSiswa($siswa_id)`

- Tampilkan semua history rombel siswa dari awal

---

### `RombelController`

#### `tabel()`

- Filter `semester_id` dari session
- Kirim `is_locked` (ada anggota aktif atau tidak) ke frontend
- Kirim `can_edit` via Policy, `can_delete` via role check

#### `store()`

- Tentukan `sekolah_id`: Admin dari request, Operator dari PTK
- Cek duplikasi nama rombel di sekolah & semester yang sama
- Set `semester_id` dari session

#### `update($id)`

- Otorisasi via `Gate::authorize('update', $rombel)`
- Jika sudah ada anggota aktif → **tingkat tidak bisa diubah**
- `semester_id` dan `sekolah_id` tidak bisa diubah (di-unset dari validated)

#### `destroy($id)`

- Cek anggota aktif dengan `whereNull('end_date')`
- Jika masih ada anggota → tolak dengan info jumlah siswa

---

### `SiswaController`

#### `tabel(Request $request)`

- Filter berdasarkan `semester_id` dari session
- Jika ada `rombel_id` di request → mode Guru Kelas (filter per rombel)
- Guru Kelas diproteksi: hanya bisa lihat rombel miliknya
- Eager load `rombelAnggota` filter `semester_id` langsung (bukan `tahun_pelajaran`)

---

### `WaliKelasController`

#### `getRombelMilikGuru($rombel_id)` — private helper

- Pastikan rombel milik guru yang login (`ptk_id === user->ptk->id`)
- Dipakai oleh semua method

#### `dataSiswa($rombel_id)`

- Return view `content.siswa.index` dengan `$rombel`
- DataTable akan filter via `?rombel_id=X` ke `/siswa/tabel`

---

## 6. POLICY & OTORISASI

### `RombelPolicy`

**Lokasi:** `app/Policies/RombelPolicy.php`
**Terdaftar di:** `AppServiceProvider::$policies`

| Method          | Siapa yang boleh                                      |
| --------------- | ----------------------------------------------------- |
| `viewAny`       | admin, Operator Sekolah                               |
| `view`          | admin, Operator Sekolah, Guru Kelas (rombel miliknya) |
| `create`        | admin, Operator Sekolah                               |
| `update`        | admin, Operator Sekolah, Guru Kelas (rombel miliknya) |
| `delete`        | tidak ada (return false)                              |
| `manageMembers` | admin, Operator Sekolah, Guru Kelas (rombel miliknya) |

> ⚠️ Nama role **case sensitive** di Spatie. Pastikan konsisten dengan yang ada di database.
> Cek: `SELECT name FROM roles;`

---

## 7. MENU DINAMIS

### `MenuServiceProvider`

**Lokasi:** `app/Providers/MenuServiceProvider.php`
**Fungsi:** Load JSON menu + inject menu rombel dinamis untuk Guru Kelas

```
verticalMenu.json  ──┐
                     ├── injectMenuRombel() ──→ $menuData ke semua view
horizontalMenu.json ─┘
```

### Struktur Menu Rombel yang Diinjek

```php
(object) [
    'icon'    => '...',
    'name'    => $rombel->nama_rombel,
    'slug'    => 'wali.kelas.' . $rombel->id,  // unik per rombel
    'roles'   => ['Guru Kelas'],
    'submenu' => [
        (object) [
            'url'       => '/wali-kelas/' . $rombel->id . '/siswa',
            'name'      => 'Data Siswa',
            'slug'      => 'wali.kelas.siswa',
            'url_check' => '/wali-kelas/' . $rombel->id . '/siswa', // untuk highlight
        ],
        // ...
    ],
]
```

### Highlight Menu Aktif

- Menu biasa: cocokkan `$currentRouteName === $menu->slug`
- Menu dinamis wali kelas: cocokkan `$currentUrl === $sub->url_check`
- **Jika ada `url_check` → gunakan HANYA url_check, jangan cek route name**
- Ini penting karena semua submenu wali kelas punya slug yang sama

> ⚠️ `url_check` adalah field tambahan di object menu untuk membedakan
> rombel yang satu dengan yang lain saat highlight sidebar

---

## 8. VIEW & BLADE

### Layout Utama (`commonMaster.blade.php`)

```blade
<body class="{{ isset($isEditable) && !$isEditable ? 'semester-locked' : '' }}">
```

### CSS Semester Lock

```css
.semester-locked .btn-tambah,
.semester-locked .btn-edit,
.semester-locked .btn-hapus,
.semester-locked .btn-simpan {
  display: none !important;
}
```

### Konvensi Class Tombol

| Class        | Fungsi                  |
| ------------ | ----------------------- |
| `btn-tambah` | Tombol tambah data baru |
| `btn-edit`   | Tombol edit per baris   |
| `btn-hapus`  | Tombol hapus per baris  |
| `btn-simpan` | Tombol submit form      |

> ⚠️ Tombol **lihat anggota** (icon group) tidak perlu class `btn-edit`
> agar tetap tampil meski semester terkunci

### Navbar

- Tidak ada lagi dropdown pilih semester (sudah dipilih saat login)
- Tampilkan nama semester sebagai teks: `| {{ $nama_semester ?? '-' }}`
- Badge "Terkunci" jika `!$isEditable`

### Halaman Wali Kelas

- Pakai view `content.siswa.index` yang sama dengan halaman siswa
- Bedanya ada `data-rombel-id` di tabel untuk filter DataTable
- Tab navigasi: Data Siswa | Absensi | (fitur lain)

---

## 9. SEMESTER LOCK (PROTEKSI DATA)

### Tiga Lapisan Proteksi

```
Lapisan 1 — UI (CSS)
    .semester-locked menyembunyikan tombol
    → Mencegah user klik tombol yang tidak seharusnya

Lapisan 2 — Middleware (CheckSemesterLock)
    Blokir semua POST/PUT/DELETE jika semester tidak aktif
    → Mencegah request langsung via form/fetch

Lapisan 3 — Controller
    Cek is_active sebelum proses data
    → Mencegah bypass via API/Postman
```

### Siapa yang Bisa Edit Semester Lama

- **Admin** → bisa edit semua semester (bypass semua lapisan)
- **Operator/Guru** → hanya semester yang `is_active = 1`

---

## 10. ROUTE

### Grup Route

```php
// Publik (guest)
GET  /login          → LoginBasic@index (dengan $semesters)
POST /login          → Fortify AuthenticatedSessionController@store
POST /logout         → Fortify (redirect via LogoutResponse)

// Admin only
GET|POST /semester/...   → SemesterController

// Admin & Operator Sekolah
GET|POST /rombel/...     → RombelController
GET|POST /siswa/...      → SiswaController
GET|POST /ptk/...        → PtkController

// Admin & Operator & Guru Kelas (untuk tabel siswa)
GET /siswa/tabel         → SiswaController@tabel

// Anggota Rombel
GET  /anggota-rombel/list/{id}      → RombelAnggotaController@rombelAnggota
GET  /anggota-rombel/tersedia/{id}  → RombelAnggotaController@listSiswaTersedia
POST /anggota-rombel/store          → RombelAnggotaController@store
DELETE /anggota-rombel/destroy/{id} → RombelAnggotaController@destroy

// Guru Kelas only
GET /wali-kelas/{rombel_id}/siswa    → WaliKelasController@dataSiswa
GET /wali-kelas/{rombel_id}/absensi  → WaliKelasController@absensi
```

---

## 11. DEPENDENSI ANTAR KOMPONEN

```
AppServiceProvider
    ├── LoginResponse      ← semester_id masuk ke session
    ├── LogoutResponse     ← redirect ke /login
    └── View::composer('*') ← kirim $nama_semester, $isEditable

MenuServiceProvider
    └── View::composer('*') ← kirim $menuData
        ⚠️ Jangan timpa variabel dari AppServiceProvider

SemesterSessionMiddleware
    └── Bergantung pada: session('semester_id')

CheckSemesterLock
    └── Bergantung pada: session('semester_id'), Semester::find()

RombelController
    └── Bergantung pada: session('semester_id'), RombelPolicy

AnggotaRombelController
    └── Bergantung pada: AnggotaRombel.semester_id, AnggotaRombel.end_date

SiswaController
    └── Bergantung pada: session('semester_id'), SekolahScope

WaliKelasController
    └── Bergantung pada: Rombel.ptk_id === user.ptk.id
```

---

## 12. HAL YANG PERLU DIPERHATIKAN

### ⚠️ Sering Jadi Masalah

1. **Nama role case sensitive** — `'admin'` ≠ `'Admin'` ≠ `'ADMIN'`

   - Cek konsistensi: `SELECT name FROM roles;`
   - Pastikan sama di: Policy, Middleware, route `role:`, blade `@role`

2. **`end_date` untuk keluar rombel** — Jangan `delete()`, gunakan:

   ```php
   $anggota->update(['end_date' => now()]);
   ```

   Dan selalu filter `whereNull('end_date')` untuk data aktif

3. **`semester_id` di `tabel_anggota_rombel`** — Harus diisi saat insert:

   ```php
   'semester_id' => $semester->id, // wajib ada
   ```

4. **View Composer `'*'` ganda** — `AppServiceProvider` dan `MenuServiceProvider`
   keduanya pakai `View::composer('*', ...)`. Pastikan tidak saling timpa variabel.

5. **`url_check` di menu wali kelas** — Wajib ada agar highlight sidebar benar.
   Jika dihilangkan, semua menu wali kelas akan aktif sekaligus.

6. **`registrasiTerbaru()` sudah `latestOfMany()`** — Tidak perlu subquery `MAX(id)` manual

7. **`is_locked` di rombel** — Dikirim dari `tabel()` sebagai metadata.
   Dipakai di JS untuk disable input tingkat saat form edit dibuka.

8. **Relasi `ptk()` di User harus `public`** — Jika `protected`, Policy tidak bisa akses

### Fitur yang Belum Selesai

- [ ] Absensi wali kelas (placeholder)
- [ ] Route `ganti.semester` bisa dihapus jika dropdown semester di navbar sudah dihapus
- [ ] `SemesterSessionController` bisa dihapus jika dropdown sudah tidak ada
