# Dokumentasi Sistem Fitur Per Sekolah

## 1. Ringkasan

Sistem ini mengatur **fitur mana yang bisa diakses oleh sekolah tertentu**. Setiap sekolah bisa punya kombinasi fitur aktif yang berbeda — misalnya Sekolah A punya fitur SKHU aktif tapi E-rapor nonaktif, sementara Sekolah B sebaliknya.

Konsepnya mirip _feature flag_, tapi di-scope **per sekolah**, bukan global per aplikasi.

## 2. Struktur Database

Ada 3 tabel yang terlibat:

```
tabel_fitur                    tabel_fitur_sekolah (PIVOT)         tabel_sekolah
+----+----------+              +----+------------+----------+---+  +----+--------+
| id | kode     |              | id | sekolah_id | fitur_id |akt|  | id | npsn   |
+----+----------+              +----+------------+----------+---+  +----+--------+
| 1  | skhu     | <-----------  | 1  | 1          | 1        | 1 |  | 1  | 123... |
| 2  | skl      | <-----------  | 2  | 1          | 2        | 0 |->| 1  | 123... |
| 3  | erapor   |              +----+------------+----------+---+  +----+--------+
+----+----------+
```

- **`tabel_fitur`** — master data seluruh fitur yang ada di sistem (kode, nama, deskripsi). Cukup punya `id` sendiri, tidak butuh referensi ke tabel lain.
- **`tabel_fitur_sekolah`** — tabel pivot (many-to-many) yang menyimpan **kombinasi** sekolah + fitur, beserta status `aktif` (boolean). Status aktif/nonaktif **selalu ada di tabel pivot ini**, bukan di `tabel_fitur`.
- **`tabel_sekolah`** — data sekolah, tidak menyimpan info fitur langsung, hanya lewat relasi many-to-many ke `tabel_fitur`.

### Kenapa perlu tabel pivot?

Karena hubungannya **many-to-many**: satu fitur bisa dipakai banyak sekolah, dan satu sekolah bisa punya banyak fitur. Status aktif itu spesifik untuk **kombinasi** sekolah+fitur tertentu, bukan properti global milik fitur itu sendiri.

## 3. Model & Relasi

### `App\Models\Fitur`

```php
public function sekolahs()
{
    return $this->belongsToMany(Sekolah::class, 'tabel_fitur_sekolah')
        ->withPivot('aktif')
        ->withTimestamps();
}
```

### `App\Models\Sekolah`

```php
public function fiturs()
{
    return $this->belongsToMany(Fitur::class, 'tabel_fitur_sekolah')
        ->withPivot('aktif')
        ->withTimestamps();
}

public function hasFitur(string $kode): bool
{
    return $this->fiturs()
        ->where('kode', $kode)
        ->wherePivot('aktif', true)   // WAJIB pakai wherePivot, bukan where biasa
        ->exists();
}
```

> ⚠️ **Penting**: kolom `aktif` ada di tabel **pivot**, bukan di `tabel_fitur`. Selalu gunakan `wherePivot('aktif', true)`, jangan `where('aktif', true)` — kesalahan ini adalah bug paling umum di sistem ini.

## 4. Helper — `App\Helpers\FiturHelper`

Semua pengecekan status fitur **wajib** lewat helper ini, jangan query manual di controller/tempat lain.

```php
FiturHelper::punya(string $kode): bool
```

Cek apakah **sekolah milik user yang sedang login** punya fitur tertentu aktif.

- Admin selalu `true` untuk semua fitur (bypass).
- User lain dicek berdasarkan `$user->ptk->sekolah_id`.
- Hasil di-cache per sekolah + kode fitur (`fitur_{sekolahId}_{kode}`, TTL 300 detik).

```php
FiturHelper::semua(): array
```

Ambil semua kode fitur yang aktif untuk sekolah user yang login. Dipakai untuk render daftar menu.

```php
FiturHelper::clearCache(int $sekolahId): void
```

Hapus seluruh cache fitur milik satu sekolah. **Wajib dipanggil** setiap kali status fitur sekolah diubah (lewat `toggleFitur`), agar perubahan langsung terasa tanpa menunggu cache expired.

## 5. Middleware — `App\Http\Middleware\CheckFitur`

Dipakai untuk memproteksi **route** agar tidak bisa diakses langsung lewat URL kalau fiturnya nonaktif — meski menu sudah disembunyikan di UI, proteksi di level route tetap wajib ada.

### Cara pakai di route

```php
Route::middleware(['fitur:skhu'])->group(function () {
    Route::controller(SkhuController::class)
        ->prefix('skhu')
        ->group(function () {
            Route::get('/', 'index')->name('skhu');
            // ...
        });
});
```

String setelah `fitur:` (misal `skhu`) **harus persis sama** (case-sensitive) dengan kolom `kode` di `tabel_fitur`.

### Jika fitur nonaktif

Middleware melempar `App\Exceptions\FiturNonaktifException`, yang ditangani khusus (lihat bagian 7) untuk menampilkan halaman informatif — bukan halaman 403 generik.

## 6. Menampilkan/Menyembunyikan Menu Berdasarkan Fitur

Filtering menu berdasarkan fitur **digabung** dengan filtering berdasarkan role, dilakukan terpusat di `App\Providers\MenuServiceProvider`. Lihat dokumentasi lengkap alur menu di **`MENU.md`**.

Ringkasnya: setiap item menu di file JSON (`resources/menu/verticalMenu.json`, `horizontalMenu.json`) bisa punya key opsional:

```json
{
  "url": "/skhu",
  "name": "SKHU",
  "slug": "skhu",
  "roles": ["Operator Sekolah"],
  "fitur": "skhu"
}
```

`MenuServiceProvider` akan memfilter item ini keluar dari daftar menu (termasuk menyembunyikan header section kalau seluruh isinya tidak lolos filter) jika:

- Fitur `skhu` tidak aktif untuk sekolah user, **atau**
- User tidak punya salah satu role di `roles`.

## 7. Menangani Fitur Nonaktif dengan Halaman Informatif (bukan 403 polos)

### Kenapa tidak pakai `abort(403, 'text')` biasa?

Karena user butuh tahu **kenapa** ditolak dan **apa yang harus dilakukan** (hubungi admin untuk aktifkan fitur) — bukan sekadar pesan generik "tidak berhak", yang bisa membuat user salah paham seolah dia memang tidak berhak secara permanen.

### Custom Exception

```php
// app/Exceptions/FiturNonaktifException.php
class FiturNonaktifException extends Exception
{
    public function __construct(
        public string $kodeFitur,
        string $message = 'Fitur ini tidak tersedia untuk sekolah Anda.'
    ) {
        parent::__construct($message);
    }
}
```

### Middleware melempar exception ini

```php
public function handle(Request $request, Closure $next, string $kode)
{
    if (!FiturHelper::punya($kode)) {
        throw new FiturNonaktifException($kode);
    }
    return $next($request);
}
```

### Ditangani di `bootstrap/app.php`

```php
$exceptions->render(function (FiturNonaktifException $e, $request) {
    if ($request->expectsJson()) {
        return response()->json([
            'message' => $e->getMessage(),
            'kode_fitur' => $e->kodeFitur,
        ], 403);
    }

    return response()->view('errors.fitur-nonaktif', [
        'kodeFitur' => $e->kodeFitur,
    ], 403);
});
```

View `errors/fitur-nonaktif.blade.php` menampilkan pesan spesifik nama fitur + tombol "Hubungi Admin", bukan sekadar "Kembali ke Beranda" seperti halaman 403 generik.

## 8. Alur Kerja End-to-End

```
Admin toggle fitur di UI (checkbox aktif/nonaktif)
        │
        ▼
POST /toggle-fitur  ──▶  Cek otorisasi (hanya Admin yang boleh)
        │
        ▼
FiturSekolah::updateOrCreate([sekolah_id, fitur_id], ['aktif' => ...])
        │
        ▼
FiturHelper::clearCache($sekolahId)
        │
        ▼
User (Operator/Guru/dst) buka aplikasi
        │
        ▼
MenuServiceProvider filter menu ──▶ FiturHelper::punya($kode)
        │                                  │
        ▼                                  ▼
Menu tampil/hilang sesuai status    (Cache di-refresh otomatis
                                      karena sudah di-clear tadi)
        │
        ▼
User klik menu ──▶ route dengan middleware `fitur:kode`
        │
        ▼
   Fitur aktif?
   ├─ Ya  → lanjut ke controller
   └─ Tidak → FiturNonaktifException → halaman informatif
```

## 9. Menambah Fitur Baru

1. **Tambahkan ke seeder** (`database/seeders/FiturSeeder.php`), bukan lewat form manual — supaya konsisten di semua environment dan ter-track di Git:
   ```php
   Fitur::updateOrCreate(
       ['kode' => 'presensi_wajah'],
       ['nama' => 'Presensi Wajah', 'deskripsi' => 'Absensi menggunakan pengenalan wajah']
   );
   ```
2. Tambahkan middleware `fitur:presensi_wajah` di route yang relevan.
3. Tambahkan key `"fitur": "presensi_wajah"` di item menu JSON yang sesuai.
4. Jalankan seeder: `php artisan db:seed --class=FiturSeeder`.

> ⚠️ Kode fitur (`kode`) sebaiknya **tidak diubah** setelah dipakai di production — karena sudah ter-hardcode di route middleware dan file menu JSON. Kalau kode berubah, semua referensi itu putus tanpa error yang jelas (fitur akan selalu dianggap nonaktif).

## 10. Checklist Debugging Jika Fitur Tidak Berfungsi Seperti Harapan

- [ ] Cek kode fitur di database: `Fitur::pluck('kode')` — pastikan sama persis (case-sensitive) dengan yang dipakai di middleware & JSON menu.
- [ ] Cek apakah query pengecekan aktif pakai `wherePivot('aktif', true)`, bukan `where('aktif', true)`.
- [ ] Cek apakah cache sudah di-clear setelah toggle (`FiturHelper::clearCache`) — kalau lupa, perubahan tidak langsung terasa sampai cache TTL habis (300 detik).
- [ ] Cek relasi `$user->ptk` — pastikan `hasOne`, bukan collection, karena helper mengakses `$user->ptk->sekolah_id` langsung.
