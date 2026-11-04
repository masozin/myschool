# Panduan Alur Kerja — Sistem Manajemen Sekolah

> Terakhir diperbarui: April 2026  
> Framework: Laravel 11 + Jetstream + Spatie Permission + DomPDF

---

## DAFTAR ISI

1. [Stack & Dependensi](#1-stack--dependensi)
2. [Struktur Role & Akses](#2-struktur-role--akses)
3. [Arsitektur Sistem](#3-arsitektur-sistem)
4. [Alur Autentikasi & Session](#4-alur-autentikasi--session)
5. [Struktur Database](#5-struktur-database)
6. [Model & Relasi Penting](#6-model--relasi-penting)
7. [Middleware](#7-middleware)
8. [Menu Dinamis](#8-menu-dinamis)
9. [Semester Lock](#9-semester-lock)
10. [Fitur Pro per Sekolah](#10-fitur-pro-per-sekolah)
11. [Modul Siswa & Rombel](#11-modul-siswa--rombel)
12. [Modul SKHU](#12-modul-skhu)
13. [Modul Data Master Mapel](#12a-modul-data-master-mapel-admin-panel)
14. [API & Halaman Publik Cek SKHU](#12b-api--halaman-publik-cek-skhu)
15. [Import Excel Nilai SKHU](#12c-import-excel-nilai-skhu)
16. [Konvensi Kode](#13-konvensi-kode)
17. [Hal yang Sering Jadi Masalah](#14-hal-yang-sering-jadi-masalah)
18. [Fitur yang Belum Selesai](#15-fitur-yang-belum-selesai)

---

## 1. STACK & DEPENDENSI

| Komponen          | Detail                                    |
| ----------------- | ----------------------------------------- |
| Framework         | Laravel 11                                |
| Auth              | Laravel Jetstream + Fortify               |
| Role & Permission | Spatie Laravel Permission                 |
| Template Admin    | Materialize Bootstrap                     |
| DataTable         | DataTables.js (server-side & client-side) |
| Form Validation   | FormValidation.js                         |
| PDF               | barryvdh/laravel-dompdf                   |
| Excel Import      | maatwebsite/excel                         |
| Storage           | Laravel Storage (public disk)             |

```bash
# Install dependensi utama
composer require spatie/laravel-permission
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
```

---

## 2. STRUKTUR ROLE & AKSES

> ⚠️ Role bersifat **case sensitive** di Spatie. Selalu gunakan nama persis seperti di bawah.

| Role             | Nama di DB         | Akses                          |
| ---------------- | ------------------ | ------------------------------ |
| Super Admin      | `Admin`            | Semua fitur, semua sekolah     |
| Operator Sekolah | `Operator Sekolah` | Kelola data sekolahnya sendiri |
| Guru Kelas       | `Guru Kelas`       | Lihat data kelas yang dia ampu |

### Cara cek role di kode

```php
// Controller / PHP
Auth::user()->hasRole('Admin')
Auth::user()->hasRole('Operator Sekolah')
Auth::user()->hasRole(['Admin', 'Operator Sekolah']) // salah satu

// Blade
@role('Admin') ... @endrole
@hasanyrole('Admin|Operator Sekolah') ... @endhasanyrole

// JS — fungsi global tersedia di semua halaman
window.hasRole('Admin')
window.hasRole('Operator Sekolah')
```

### Cek nama role di database

```sql
SELECT name FROM roles;
```

---

## 3. ARSITEKTUR SISTEM

```
resources/menu/verticalMenu.json   ← definisi menu sidebar
resources/menu/horizontalMenu.json ← definisi menu horizontal
resources/views/templates/skhu-template/
  └── sd/2024-2025.blade.php       ← template SKHU per jenjang per tahun

app/
├── Http/
│   ├── Controllers/               ← semua controller
│   ├── Middleware/
│   │   ├── SemesterSessionMiddleware.php  ← set semester saat session kosong
│   │   ├── CheckSemesterLock.php          ← blokir write jika semester tidak aktif
│   │   └── CheckFitur.php                 ← blokir akses fitur pro
│   └── Responses/
│       ├── LoginResponse.php      ← set semester ke session saat login
│       └── LogoutResponse.php     ← redirect ke /login setelah logout
├── Models/
├── Helpers/
│   └── FiturHelper.php            ← cek fitur pro per sekolah
├── Imports/
│   └── SiswaImport.php            ← import siswa dari Excel
└── View/
    └── Composers/                 ← tidak dipakai lagi, diganti MenuServiceProvider

app/Providers/
├── AppServiceProvider.php         ← view composer navbar, semester, isEditable
└── MenuServiceProvider.php        ← load JSON menu + inject menu guru kelas
```

---

## 4. ALUR AUTENTIKASI & SESSION

### Login

```
User buka /login
  ↓
Dropdown semester tampil (default = semester is_active=1)
  ↓
User pilih semester → submit
  ↓
Fortify autentikasi → LoginResponse::toResponse()
  ↓
session['semester_id'] = semester yang dipilih
  ↓
Redirect ke dashboard
```

### Session Semester

- Disimpan di `session('semester_id')` — ID dari `tabel_semester`
- Diset saat login oleh `LoginResponse`
- Safety net: `SemesterSessionMiddleware` set ulang jika session hilang

### Logout

- `LogoutResponse` redirect ke `/login`
- Session otomatis dihapus Laravel

### File Terkait

| File                                                | Fungsi                                   |
| --------------------------------------------------- | ---------------------------------------- |
| `app/Http/Responses/LoginResponse.php`              | Set semester ke session saat login       |
| `app/Http/Responses/LogoutResponse.php`             | Redirect ke `/login`                     |
| `app/Http/Middleware/SemesterSessionMiddleware.php` | Safety net session                       |
| `app/Providers/AppServiceProvider.php`              | Daftarkan LoginResponse & LogoutResponse |

---

## 5. STRUKTUR DATABASE

### Tabel Utama

```
tabel_siswa                ← data identitas siswa
tabel_siswa_registrasi     ← registrasi siswa per sekolah (NIS, tingkat, status)
tabel_rombel               ← kelas/rombel per sekolah per semester
tabel_anggota_rombel       ← siswa yang masuk ke rombel (dengan end_date)
tabel_semester             ← data semester (tahun_pelajaran, is_active)
tabel_sekolah              ← data sekolah (npsn, jenjang)
tabel_sekolah_detail       ← detail sekolah per semester (nama, alamat, KS)
tabel_ptk                  ← data guru/PTK per sekolah
```

### Tabel Fitur Pro

```
tabel_fitur                ← daftar fitur yang tersedia
tabel_fitur_sekolah        ← fitur yang diaktifkan per sekolah oleh admin
```

### Tabel SKHU

```
tabel_kelompok_mapel       ← kelompok mapel per jenjang (Kelompok A, B, Muatan Lokal)
tabel_mapel                ← mata pelajaran per jenjang per tahun pelajaran
tabel_template_skhu        ← registrasi template blade per jenjang per tahun
tabel_sekolah_logo       ← TTD KS, stempel, logo (dipakai semua dokumen)
tabel_skhu                 ← header SKHU per siswa (nomor surat, tanggal, status)
tabel_skhu_nilai           ← nilai per mapel per SKHU
```

### Kolom Penting

**`tabel_anggota_rombel`**

- `end_date = NULL` → siswa **masih aktif** di rombel
- `end_date = tanggal` → siswa sudah keluar (history tetap ada)
- Jangan gunakan `delete()` untuk keluarkan siswa, gunakan `update(['end_date' => now()])`

**`tabel_semester`**

- `is_active = 1` → semester yang sedang berjalan
- `tahun_pelajaran` → format `'2024/2025'` (dengan slash, bukan dash)

**`tabel_mapel`**

- `jenis = 'pokok'`, `sekolah_id = NULL` → mapel berlaku semua sekolah
- `jenis = 'tambahan'`, `sekolah_id = X` → mapel muatan lokal sekolah tertentu

---

## 6. MODEL & RELASI PENTING

### Siswa

```php
$siswa->siswaRegistrasi      // HasMany — semua registrasi
$siswa->registrasiTerbaru    // HasOne (latestOfMany) — registrasi terbaru
$siswa->rombelAnggota        // HasMany — semua riwayat rombel
$siswa->rombels              // BelongsToMany — via tabel_anggota_rombel
$siswa->skhu                 // HasMany — semua SKHU
```

> ⚠️ `registrasiTerbaru` sudah pakai `latestOfMany()` — **tidak perlu** subquery `MAX(id)` manual

### RombelAnggota

```php
// Hanya anggota aktif:
RombelAnggota::whereNull('end_date')->get()

// Keluarkan siswa (jangan delete):
$anggota->update(['end_date' => now()])
```

### Skhu

```php
$skhu->nilaiPerKelompok()   // array nilai dikelompokkan per kelompok mapel
$skhu->nilaiRataRata()      // float rata-rata semua nilai
$skhu->aset('ttd_ks')       // path absolut aset (untuk DomPDF)
$skhu->nama_sekolah         // accessor dari relasi sekolah
$skhu->kepala_sekolah       // accessor dari relasi sekolah
$skhu->tahun_pelajaran      // accessor dari relasi semester
$skhu->nis                  // accessor dari registrasiTerbaru siswa
```

### User

```php
// ⚠️ Relasi harus public, bukan protected
public function ptk(): HasOne { ... }
public function siswa(): HasOne { ... }
```

### Sekolah

```php
$sekolah->asets              // HasMany SekolahLogo
$sekolah->detailTerbaru      // HasOne — detail terbaru
$sekolah->detailPerSemester  // HasOne — detail untuk semester di session
$sekolah->fiturs             // BelongsToMany — fitur yang aktif
$sekolah->hasFitur('kode')   // bool — cek fitur aktif
```

---

## 7. MIDDLEWARE

| Alias              | Class                     | Fungsi                                           |
| ------------------ | ------------------------- | ------------------------------------------------ |
| `role`             | Spatie RoleMiddleware     | Cek role user                                    |
| `semester.session` | SemesterSessionMiddleware | Set semester default di session                  |
| `semester.lock`    | CheckSemesterLock         | Blokir POST/PUT/DELETE jika semester tidak aktif |
| `fitur`            | CheckFitur                | Blokir akses jika sekolah tidak punya fitur      |

### CheckSemesterLock

- GET request → selalu lewat
- Admin → selalu lewat
- Operator/Guru + semester tidak aktif → 403

### CheckFitur

```php
// Di route
Route::middleware('fitur:skhu')->group(function () { ... });
```

---

## 8. MENU DINAMIS

### Alur

```
MenuServiceProvider::boot()
  ↓
Load verticalMenu.json + horizontalMenu.json
  ↓
filterMenuFitur() — sembunyikan menu yang butuh fitur pro
  ↓
injectMenuRombel() — tambah menu kelas untuk Guru Kelas
  ↓
$view->with('menuData', [$vertical, $horizontal])
```

### Format JSON Menu

```json
{
  "url": "/skhu",
  "icon": "menu-icon icon-base ri ri-file-text-line",
  "name": "SKHU",
  "slug": "page.skhu",
  "roles": ["Operator Sekolah"],
  "fitur": "skhu"
}
```

| Key     | Keterangan                                                             |
| ------- | ---------------------------------------------------------------------- |
| `roles` | Array role yang boleh lihat menu ini                                   |
| `fitur` | Kode fitur pro — menu disembunyikan jika sekolah tidak punya fitur ini |
| `slug`  | Harus sama persis dengan `name` route untuk highlight aktif            |

### Highlight Menu Aktif

- Menu biasa: cocokkan `currentRouteName === menu.slug`
- Menu dinamis Guru Kelas: cocokkan `currentUrl === sub.url_check`
- Jika ada `url_check` → gunakan **hanya** url_check, jangan cek route name

### Icon

> ⚠️ Semua icon harus pakai class `icon-base ri ri-nama-icon`

```html
<i class="icon-base ri ri-home-line"></i>
```

---

## 9. SEMESTER LOCK

### Tiga Lapisan Proteksi

```
Lapisan 1 — CSS (body.semester-locked)
  .semester-locked .btn-tambah,
  .semester-locked .btn-edit,
  .semester-locked .btn-hapus,
  .semester-locked .btn-simpan,
  .semester-locked .sem-lock { display: none !important; }

Lapisan 2 — Middleware CheckSemesterLock
  Blokir semua POST/PUT/DELETE jika semester tidak aktif

Lapisan 3 — Controller
  Cek is_active sebelum proses data
```

### Variabel `$isEditable`

- Dikirim ke semua view oleh `AppServiceProvider`
- `true` jika Admin ATAU semester yang dipilih `is_active = 1`
- Dipakai di blade: `@if($isEditable)` dan di body class

### Class Tombol yang Dikontrol CSS

| Class        | Fungsi                           |
| ------------ | -------------------------------- |
| `btn-tambah` | Tombol tambah data baru          |
| `btn-edit`   | Tombol edit per baris            |
| `btn-hapus`  | Tombol hapus per baris           |
| `btn-simpan` | Tombol submit form               |
| `sem-lock`   | Komponen lain yang perlu dikunci |

---

## 10. FITUR PRO PER SEKOLAH

### Konsep

- Admin aktifkan/nonaktifkan fitur per sekolah via toggle di halaman detail sekolah
- Cache 5 menit per sekolah per fitur

### FiturHelper

```php
FiturHelper::punya('skhu')     // bool — cek fitur untuk user yang login
FiturHelper::semua()           // array kode fitur aktif
FiturHelper::clearCache($id)   // hapus cache saat admin ubah fitur
```

### Tambah Fitur Baru

1. Insert ke `tabel_fitur`: `INSERT INTO tabel_fitur (kode, nama) VALUES ('kode_baru', 'Nama Fitur')`
2. Tambah key `"fitur": "kode_baru"` di JSON menu jika perlu
3. Tambah middleware `fitur:kode_baru` di route yang perlu diproteksi

---

## 11. MODUL SISWA & ROMBEL

### Alur Tambah Siswa ke Rombel

```
listSiswaTersedia($rombel_id)
  ↓
Filter A: registrasiTerbaru aktif & sekolah sama
Filter B: belum punya rombelAnggota aktif (end_date NULL) di semester ini
Filter C: kelayakan tingkat
  - Ganjil: siswa baru (tingkat sesuai) ATAU naik/tinggal kelas
  - Genap: ada di semester ganjil yang sama ATAU siswa baru/mutasi
```

### Keluarkan Siswa dari Rombel

```php
// JANGAN delete, gunakan end_date
$anggota->update(['end_date' => now()]);
```

### Import Excel Siswa

- File: `app/Imports/SiswaImport.php`
- Template: `public/templates/template-import-siswa.xlsx`
- Validasi dua tahap: semua baris divalidasi dulu, baru insert jika 0 error
- Deteksi baris keterangan otomatis (baris ke-2 setelah heading)
- Case insensitive: `jenis_pendaftaran` (baru/BARU/Baru semua diterima)
- Disabilitas kosong → default `'Tidak ada'`

### Tabel Siswa — Filter DataTable

- Operator: filter berdasarkan semester aktif di session
- Admin: ada dropdown filter sekolah
- Guru Kelas: hanya siswa di rombel miliknya (`?rombel_id=X`)

---

## 12. MODUL SKHU

### Perubahan Terbaru (v3)

| Perubahan                  | Keterangan                                                                                                               |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| Hapus `template_skhu_id`   | Kolom dihapus dari `tabel_skhu` dan model. Template dicari berdasarkan `sekolah_id` + `jenjang` saat cetak PDF           |
| Lock SKHU Final            | SKHU berstatus `final` tidak bisa diedit — semua field dan input nilai di-disable di form                                |
| Tombol Batalkan Finalisasi | Banner muncul di form jika SKHU sudah final, ubah status kembali ke `draft` via `POST /skhu/siswa/{id}/batal-final`      |
| Finalisasi Semua           | Tombol di toolbar `skhu/index` — finalisasi semua SKHU draft sekolah di semester aktif via `POST /skhu/finalisasi-semua` |

### Alur Generate SKHU

```
Operator buka /skhu
  ↓
Sistem ambil siswa di rombel tingkat akhir (6/9/12) semester aktif
  ↓
Operator klik siswa → form input nilai
  ↓
Input nomor surat, nomor ujian, tanggal, nilai per mapel
  ↓
Simpan Draft / Finalisasi
  ↓
Download PDF per siswa atau bulk ZIP semua angkatan
```

### Tingkat Akhir per Jenjang

| Jenjang | Tingkat Akhir |
| ------- | ------------- |
| SD      | 6             |
| SMP     | 9             |
| SMA     | 12            |
| SMK     | 12            |

### Template Blade

- Lokasi: `resources/views/templates/skhu-template/{jenjang}/{tahun}.blade.php`
- Contoh: `resources/views/templates/skhu-template/sd/2024-2025.blade.php`
- Variabel yang tersedia di template: `$skhu` (object Skhu dengan semua relasi)
- Dibuat/edit langsung di server (tidak ada UI upload template)

### Registrasi Template di DB

```sql
INSERT INTO tabel_template_skhu (jenjang, tahun_pelajaran, nama_file, is_active)
VALUES ('SD', '2025/2026', 'sd/2025-2026', 1);
```

### Mapel

- `jenis = 'pokok'`, `sekolah_id = NULL` → berlaku semua sekolah (dikelola Admin)
- `jenis = 'tambahan'`, `sekolah_id = X` → muatan lokal (ditambah Operator di halaman SKHU)
- Query mapel: `Mapel::untukSekolah($jenjang, $tahunPelajaran, $sekolahId)->get()`

### Aset Sekolah (untuk dokumen)

| Tipe             | Keterangan                  |
| ---------------- | --------------------------- |
| `ttd_ks`         | Tanda tangan kepala sekolah |
| `stempel`        | Stempel/cap sekolah         |
| `logo_sekolah`   | Logo sekolah                |
| `logo_kabupaten` | Logo kabupaten/kota         |

```php
// Di template blade SKHU
$skhu->aset('ttd_ks')        // path absolut (untuk DomPDF)
$skhu->aset('logo_kabupaten')
```

### DomPDF — Catatan Penting

- Gunakan path absolut untuk gambar, bukan URL: `$aset->absolute_path`
- `isRemoteEnabled = false` — gambar dari storage harus pakai path lokal
- Format tanggal bahasa Indonesia: `Carbon::setLocale('id')` di AppServiceProvider

### Admin Panel Mapel (Tahap 4 — belum selesai)

- CRUD Kelompok Mapel per jenjang
- CRUD Mapel Pokok per jenjang per tahun pelajaran
- Fitur **Salin Tahun Pelajaran** — duplikat semua mapel ke tahun berikutnya

---

## 12a. MODUL DATA MASTER MAPEL (Admin Panel)

> Hanya dapat diakses oleh role **Admin**.

### Lokasi File

| File         | Path                                           |
| ------------ | ---------------------------------------------- |
| Controller   | `app/Http/Controllers/MapelController.php`     |
| Blade        | `resources/views/master/mapel/index.blade.php` |
| Route prefix | `/master/mapel`                                |

### Route yang Tersedia

| Method | URL                                  | Action                | Keterangan                                     |
| ------ | ------------------------------------ | --------------------- | ---------------------------------------------- |
| GET    | `/master/mapel`                      | `index`               | Tampilkan daftar mapel, filter jenjang & tahun |
| POST   | `/master/mapel`                      | `store`               | Tambah mapel pokok baru                        |
| PUT    | `/master/mapel/{mapel}`              | `update`              | Edit mapel pokok                               |
| DELETE | `/master/mapel/{mapel}`              | `destroy`             | Hapus mapel (gagal jika sudah ada nilai SKHU)  |
| POST   | `/master/mapel/{mapel}/urutan-naik`  | `urutanNaik`          | Tukar urutan dengan mapel di atasnya           |
| POST   | `/master/mapel/{mapel}/urutan-turun` | `urutanTurun`         | Tukar urutan dengan mapel di bawahnya          |
| POST   | `/master/mapel/salin`                | `salinTahunPelajaran` | Duplikat semua mapel pokok ke tahun baru       |

### Aturan Penting

- Halaman ini **hanya mengelola mapel pokok** (`jenis = 'pokok'`, `sekolah_id = NULL`).
- Mapel tambahan/muatan lokal (`jenis = 'tambahan'`) dikelola oleh Operator di halaman SKHU.
- Hapus mapel **dicegah** jika mapel sudah punya data di `tabel_skhu_nilai`.
- Salin Tahun Pelajaran **dicegah** jika tahun tujuan sudah punya data mapel untuk jenjang yang sama.

### Salin Tahun Pelajaran

```
Admin pilih jenjang + tahun sumber → isi tahun tujuan (format YYYY/YYYY)
  ↓
Sistem validasi: tahun tujuan belum punya mapel?
  ↓
Duplikat semua kolom kecuali id dan timestamps
  ↓
Redirect otomatis ke halaman tahun tujuan
```

### Tambah ke Menu Sidebar

Tambahkan di `resources/menu/verticalMenu.json` pada bagian yang sesuai:

```json
{
  "url": "/master/mapel",
  "name": "Mata Pelajaran",
  "icon": "ri-book-2-line",
  "slug": "master-mapel"
}
```

---

## 12b. API & HALAMAN PUBLIK CEK SKHU

> Dapat diakses oleh **siapa saja tanpa login**.

### Lokasi File

| File       | Path                                            |
| ---------- | ----------------------------------------------- |
| Controller | `app/Http/Controllers/SkhuPublikController.php` |
| Blade      | `resources/views/publik/skhu/cari.blade.php`    |
| Route web  | `routes/web.php` → `/cek-skhu`                  |
| Route API  | `routes/api.php` → `/api/skhu/{nisn}`           |

### Endpoint

| Method | URL                | Keterangan                              |
| ------ | ------------------ | --------------------------------------- |
| GET    | `/cek-skhu`        | Halaman pencarian publik (layout front) |
| GET    | `/api/skhu/{nisn}` | API JSON — NISN harus 10 digit angka    |

### Response API: Sukses (200)

```json
{
  "status": "ok",
  "siswa": {
    "nama": "...",
    "nisn": "...",
    "tempat_lahir": "...",
    "tanggal_lahir": "...",
    "nama_ayah": "..."
  },
  "skhu_list": [
    {
      "id": 1,
      "nomor_surat": "...",
      "nomor_ujian": "...",
      "tanggal_mulai_ujian": "...",
      "tanggal_selesai_ujian": "...",
      "tanggal_terbit": "...",
      "tahun_pelajaran": "2024/2025",
      "sekolah": { "nama": "...", "npsn": "...", "jenjang": "SD", "kepala_sekolah": "..." },
      "nilai_rata_rata": 85.5,
      "nilai_per_kelompok": [
        {
          "kelompok": "Kelompok A",
          "mapels": [{ "nama_mapel": "...", "nama_mapel_sub": null, "nilai": "90.00" }]
        }
      ]
    }
  ]
}
```

### Response API: Gagal

| Status | Kondisi                                     |
| ------ | ------------------------------------------- |
| 422    | Format NISN tidak valid (bukan 10 digit)    |
| 404    | NISN tidak ditemukan / belum ada SKHU final |

### Aturan Penting

- **Hanya SKHU berstatus `final`** yang ditampilkan. SKHU draft tidak muncul ke publik.
- Jika siswa memiliki lebih dari 1 SKHU (beda jenjang/sekolah), semua ditampilkan dengan tab.
- Halaman menggunakan layout `front` (navbar publik + footer front).
- Route `/api/skhu/{nisn}` didaftarkan di `routes/api.php` dengan prefix `/api` otomatis dari Laravel.
- Route dibatasi regex `[0-9]{10}` — request dengan NISN non-angka langsung 404 tanpa masuk controller.

### Tambah Link di Navbar Front

Di `resources/views/layouts/sections/navbar/navbar-front.blade.php`, tambahkan:

```html
<li class="nav-item">
  <a class="nav-link fw-medium" href="{{ url('/cek-skhu') }}">Cek SKHU</a>
</li>
```

---

## 12c. IMPORT EXCEL NILAI SKHU

> Hanya dapat dilakukan oleh role **Admin**.

### Lokasi File

| File              | Path                                                           |
| ----------------- | -------------------------------------------------------------- |
| Import Class      | `app/Imports/SkhuImport.php`                                   |
| Blade             | Terintegrasi di `resources/views/skhu/index.blade.php` (modal) |
| Method Controller | `downloadTemplate` + `importNilai` di `SkhuController.php`     |

### Akses

- **Operator Sekolah** dan **Admin** — sekolah diambil otomatis dari `$user->ptk->sekolah_id`

### Route yang Ditambahkan

| Method | URL                             | Action             | Keterangan                         |
| ------ | ------------------------------- | ------------------ | ---------------------------------- |
| GET    | `/skhu/import/template`         | `downloadTemplate` | Generate & download Excel template |
| POST   | `/skhu/import`                  | `importNilai`      | Upload & proses file Excel         |
| PUT    | `/skhu/mapel/{id}`              | `editMapel`        | Edit mapel muatan lokal            |
| DELETE | `/skhu/mapel/{id}`              | `hapusMapel`       | Hapus mapel muatan lokal           |
| POST   | `/skhu/mapel/{id}/urutan-naik`  | `mapelUrutanNaik`  | Naikkan urutan mapel               |
| POST   | `/skhu/mapel/{id}/urutan-turun` | `mapelUrutanTurun` | Turunkan urutan mapel              |

### Format Template Excel (v3)

| Kolom           | Keterangan             | Terkunci? |
| --------------- | ---------------------- | --------- |
| A — No          | Nomor urut             | ✅ Ya     |
| B — Nama Siswa  | Nama siswa             | ✅ Ya     |
| C — NISN        | NISN                   | ✅ Ya     |
| D — Rombel      | Nama rombel            | ✅ Ya     |
| E — Nomor Surat | Bisa diisi/dikosongkan | ❌ Bebas  |
| F — Nomor Ujian | Bisa diisi/dikosongkan | ❌ Bebas  |
| G+ — Mapel      | Nilai 0–100            | ❌ Bebas  |

- **Baris 1**: Key heading (`mapel_{id}||Nama`) untuk kolom mapel; nomor urut mapel ditampilkan sebagai angka
- **Baris 2**: Label deskriptif nama mapel + kelompok (tidak diproses saat import)
- Password proteksi sheet: **`021`**
- Freeze pane setelah kolom F (mulai kolom mapel pertama)
- Urutan mapel mengikuti: `mapelKelompok.urutan` → `mapel.urutan`

### Perubahan UI skhu/index

- Tombol **"Tambah Mapel Muatan Lokal"** diganti → **"Mapel"** (buka modal daftar mapel)
- Modal mapel menampilkan semua mapel (pokok + tambahan) dengan badge jenis
- Mapel tambahan milik sekolah sendiri: bisa edit, hapus, naik/turun urutan
- Mapel pokok: hanya tampil, tidak bisa diedit dari sini
- **Fix datepicker**: flatpickr diinit pada event `shown.bs.modal` dengan `appendTo: document.body` agar kalender tidak tertutup modal

### Format Template Excel

- **Baris 1 (Heading)**: Kolom tetap (`No`, `Nama Siswa`, `NISN`, `Rombel`) + kolom mapel dengan format key `mapel_{id}||Nama Mapel`
- **Baris 2**: Label deskriptif untuk memudahkan pengisian (tidak diproses saat import)
- **Baris 3+**: Data siswa — kolom tetap terisi otomatis, kolom nilai dikosongkan

### Alur Import

```
Admin pilih sekolah → download template
  ↓
Isi nilai per siswa (0–100) di Excel
  ↓
Upload file + isi tanggal ujian & terbit
  ↓
Validasi semua baris (tahap 1) — jika ada error, semua dibatalkan
  ↓
Upsert SKHU + SkhuNilai (tahap 2) — updateOrCreate per mapel
  ↓
SKHU baru = status 'draft', SKHU lama = nilai ditimpa, header tanggal diperbarui
```

### Proteksi Template Excel

- Kolom **A–D** (No, Nama, NISN, Rombel) terkunci — tidak bisa diedit tanpa password
- Kolom nilai **E dst** bebas diedit
- Password proteksi sheet: **`021`**
- Sheet freeze pane di E3 untuk memudahkan scroll

---

## 13. KONVENSI KODE

### Naming

```php
// Tabel — snake_case dengan prefix tabel_
tabel_siswa, tabel_rombel, tabel_skhu

// Model — PascalCase
Siswa, RombelAnggota, SkhuNilai

// Controller — PascalCase + Controller
SiswaController, RombelAnggotaController, SkhuController

// Route name — dot notation
page.siswa, skhu.form-siswa, wali.kelas.siswa
```

### Scope Laravel

```php
// Di Model — dengan awalan 'scope'
public function scopeUntukSekolah($query, ...) { ... }

// Di Controller — tanpa awalan 'scope', tanpa parameter $query
Mapel::untukSekolah($jenjang, $tahun, $sekolahId)->get();
```

### Response JSON

```php
// Sukses
return response()->json(['message' => 'Berhasil.']);

// Error validasi
return response()->json(['message' => '...', 'errors' => [...]], 422);

// Forbidden
return response()->json(['message' => 'Akses ditolak.'], 403);
```

### Transaksi Database

```php
DB::beginTransaction();
try {
    // ... operasi database
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    return response()->json(['message' => $e->getMessage()], 500);
}
```

---

## 14. HAL YANG SERING JADI MASALAH

### 🔴 Role case sensitive

```php
// SALAH
hasRole('admin')

// BENAR
hasRole('Admin')
```

### 🔴 end_date untuk keluar rombel

```php
// JANGAN
$anggota->delete();

// BENAR
$anggota->update(['end_date' => now()]);

// Filter anggota aktif selalu tambahkan:
->whereNull('end_date')
```

### 🔴 Scope dipanggil salah

```php
// SALAH — scopeNamaScope tidak bisa dipanggil langsung
Mapel::scopeUntukSekolah(...)

// BENAR — tanpa 'scope'
Mapel::untukSekolah(...)
```

### 🔴 semester_id wajib diisi saat insert RombelAnggota

```php
$dataInsert = [
    'siswa_id'    => $siswaId,
    'rombel_id'   => $rombelId,
    'semester_id' => $semester->id, // ← wajib ada
    'start_date'  => now(),
    'end_date'    => null,
];
```

### 🔴 Tahun pelajaran format harus konsisten

```
BENAR:  '2024/2025'  (dengan slash)
SALAH:  '2024-2025'  (dengan dash)
```

Pastikan `tabel_semester.tahun_pelajaran` dan `tabel_mapel.tahun_pelajaran` formatnya sama.

### 🔴 View Composer ganda

`AppServiceProvider` dan `MenuServiceProvider` keduanya pakai `View::composer('*')`.
Urutan di `bootstrap/providers.php`: `MenuServiceProvider` dulu, `AppServiceProvider` belakangan.

### 🟡 registrasiTerbaru sudah latestOfMany

Tidak perlu subquery `MAX(id)` manual — cukup `$siswa->registrasiTerbaru`.

### 🟡 Relasi ptk() di User harus public

```php
// SALAH
protected function ptk(): HasOne { ... }

// BENAR
public function ptk(): HasOne { ... }
```

### 🟡 Icon harus pakai class lengkap

```html
<!-- SALAH -->
<i class="ri-home-line"></i>

<!-- BENAR -->
<i class="icon-base ri ri-home-line"></i>
```

### 🟡 DomPDF butuh path absolut untuk gambar

```php
// SALAH — URL tidak bisa dibaca DomPDF jika isRemoteEnabled=false
Storage::url($aset->path_file)

// BENAR — path absolut di filesystem
storage_path('app/public/' . $aset->path_file)
```

---

## 15. FITUR YANG BELUM SELESAI

| Fitur                        | Status     | Catatan                                                         |
| ---------------------------- | ---------- | --------------------------------------------------------------- |
| Admin Panel Mapel SKHU       | ✅ Selesai | CRUD mapel pokok, urutan ↑↓, salin tahun pelajaran — lihat §12a |
| API Publik SKHU              | ✅ Selesai | `GET /api/skhu/{nisn}` + halaman `/cek-skhu` — lihat §12b       |
| Import Excel Nilai SKHU      | ✅ Selesai | Upload Excel → upsert nilai per siswa — lihat §12c              |
| Perbaikan tampilan PDF SKHU  | ⏳ Belum   | Template blade perlu penyesuaian layout                         |
| SKL (Surat Keterangan Lulus) | ⏳ Belum   | Setelah SKHU selesai                                            |
| Fitur Absensi Wali Kelas     | ⏳ Belum   | Placeholder sudah ada                                           |
| Nilai Rapor                  | ⏳ Belum   | Modul terpisah                                                  |
