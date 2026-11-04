# Dokumentasi Sistem Menu

## 1. Ringkasan

Menu sidebar (vertical) dan navbar (horizontal) aplikasi ini dibaca dari file JSON statis, difilter secara dinamis berdasarkan **role** dan **fitur aktif** sekolah, lalu dirender lewat Blade. Ada juga mekanisme **inject menu dinamis** khusus untuk guru (menu per-rombel).

## 2. Sumber Data Menu

```
resources/menu/verticalMenu.json
resources/menu/horizontalMenu.json
```

Kedua file ini berisi struktur array `menu`, dibaca dan diproses oleh `App\Providers\MenuServiceProvider`, lalu di-inject ke **semua view** lewat `View::composer('*', ...)` dengan nama variabel `$menuData`.

## 3. Struktur Item Menu

### Item menu biasa (tanpa submenu)

```json
{
  "url": "/siswa",
  "icon": "menu-icon icon-base ri ri-user-3-line",
  "name": "Siswa",
  "slug": "siswa.",
  "roles": ["Admin", "Operator Sekolah"],
  "fitur": "kode_fitur_opsional"
}
```

### Header section (pemisah visual, bukan link)

```json
{ "menuHeader": "Data Master" }
```

### Item dengan submenu (grup)

```json
{
  "name": "Master Data",
  "icon": "menu-icon icon-base ri ri-database-2-line",
  "roles": ["Admin", "Operator Sekolah"],
  "submenu": [
    { "url": "/sekolah", "name": "Data Sekolah", "slug": "sekolah.index", "roles": ["Admin"] },
    { "url": "/siswa", "name": "Siswa", "slug": "siswa.", "roles": ["Admin", "Operator Sekolah"] }
  ]
}
```

### Field yang tersedia

| Field        | Wajib?                       | Fungsi                                                                 |
| ------------ | ---------------------------- | ---------------------------------------------------------------------- |
| `url`        | Ya (kecuali punya `submenu`) | Tujuan link                                                            |
| `name`       | Ya                           | Label yang ditampilkan                                                 |
| `icon`       | Tidak                        | Class ikon (Remix Icon)                                                |
| `slug`       | Tidak, tapi disarankan       | Untuk deteksi active-state (lihat bagian 5)                            |
| `roles`      | Tidak                        | Array role yang boleh melihat item ini                                 |
| `fitur`      | Tidak                        | Kode fitur; item hanya tampil kalau fitur ini aktif untuk sekolah user |
| `submenu`    | Tidak                        | Array item anak, struktur sama seperti item biasa                      |
| `menuHeader` | Tidak                        | Kalau ada, item ini dianggap section header, field lain diabaikan      |
| `target`     | Tidak                        | Isi `"_blank"` untuk buka di tab baru (biasanya untuk link eksternal)  |
| `badge`      | Tidak                        | Array `[warna, teks]` untuk label kecil di samping menu                |
| `url_check`  | Tidak                        | Khusus menu dinamis (lihat bagian 5.3)                                 |

## 4. Filtering Menu — `MenuServiceProvider`

Semua logic "boleh tampil atau tidak" **disatukan di PHP**, bukan di Blade. Alasannya: kalau logic-nya di Blade, mudah lupa disalin ke partial baru (misal saat menambah tampilan mobile-menu) — dengan menaruhnya di PHP, data yang sampai ke Blade manapun **sudah pasti bersih**.

```php
private function filterMenu(array $menu): array
{
    $user = Auth::user();

    // 1. Kelompokkan item per-section berdasarkan menuHeader
    $sections = [];
    foreach ($menu as $item) {
        if (isset($item->menuHeader)) {
            $sections[] = ['header' => $item, 'items' => []];
        } else {
            if (empty($sections)) {
                $sections[] = ['header' => null, 'items' => []];
            }
            $sections[count($sections) - 1]['items'][] = $item;
        }
    }

    // 2. Filter item per section (fitur + role), rekursif untuk submenu
    $menuBaru = [];
    foreach ($sections as $section) {
        $filteredItems = array_values(array_filter($section['items'], function ($item) use ($user) {
            return $this->isMenuAllowed($item, $user);
        }));

        foreach ($filteredItems as $item) {
            if (isset($item->submenu) && is_array($item->submenu)) {
                $item->submenu = $this->filterMenu($item->submenu);
            }
        }

        // 3. Sembunyikan header kalau seluruh isinya hilang
        if ($section['header'] && empty($filteredItems)) {
            continue;
        }
        if ($section['header']) {
            $menuBaru[] = $section['header'];
        }
        $menuBaru = array_merge($menuBaru, $filteredItems);
    }

    return $menuBaru;
}

private function isMenuAllowed(object $item, $user): bool
{
    if (isset($item->fitur) && !\App\Helpers\FiturHelper::punya($item->fitur)) {
        return false;
    }
    if (isset($item->roles) && (!$user || !$user->hasAnyRole($item->roles))) {
        return false;
    }
    return true;
}
```

### Poin penting

1. **Rekursif ke submenu** — item di dalam `submenu` juga difilter fitur & role-nya, bukan hanya level atas.
2. **Header ikut disembunyikan** — kalau semua item di bawah satu `menuHeader` tidak lolos filter, header itu ikut dibuang, supaya tidak ada judul section menggantung tanpa isi.
3. **`array_values()` setelah `array_filter()`** — wajib, supaya index array tidak bolong (menghindari bug tak terduga di iterasi/serialisasi selanjutnya).
4. **Kombinasi fitur + role di parent yang punya submenu** — isi `roles` di level parent sebaiknya berupa **union** dari seluruh role yang dipakai anak-anaknya. Kalau parent tidak lolos cek role, seluruh submenu di dalamnya ikut tidak muncul meski submenu itu sendiri sebenarnya lolos.

## 5. Active-State (Highlight Menu yang Sedang Dibuka)

Ini murni untuk keperluan **visual** (memberi class `active`/`active open` di sidebar), tidak mempengaruhi filtering maupun routing.

### 5.1. `slug` — dicocokkan dengan **nama route**

```php
$currentRouteName = Route::currentRouteName(); // misal "siswa.show"
```

Pencocokan pakai **prefix match** (`str_contains` + `strpos(...) === 0`), bukan exact match — supaya satu `slug` bisa mewakili seluruh grup route CRUD:

```php
// slug: "siswa."  cocok untuk:  siswa.index, siswa.show, siswa.edit, siswa.update, dst
```

> ⚠️ **Konsisten di 3 tempat**: `verticalMenu.blade.php` (item tanpa submenu), `verticalMenu.blade.php` (mengecek submenu untuk menentukan parent aktif), dan `submenu.blade.php` (item submenu itu sendiri) — ketiganya **harus** pakai logic prefix match yang sama. Exact match (`===`) di salah satu tempat adalah bug yang menyebabkan highlight tidak konsisten antar halaman dalam grup route yang sama.

### 5.2. `slug` sebagai array

Dipakai di **level parent** yang submenu-nya berasal dari beberapa modul berbeda (bukan satu grup CRUD):

```json
{
  "name": "Kurikulum",
  "slug": ["mapel.index", "mapel-mapping.index"],
  "submenu": [...]
}
```

Fungsinya sebagai **fallback terakhir**: kalau tidak ada satupun item di `submenu` yang match dengan route aktif, sistem masih mengecek slug milik parent sendiri sebelum menyerah. Berguna untuk route yang secara konsep termasuk modul ini tapi belum/tidak didaftarkan sebagai link submenu tersendiri.

### 5.3. `url_check` — untuk menu dinamis (bukan `slug`)

Dipakai khusus untuk menu yang di-generate dinamis dengan parameter di URL (misal menu rombel per-guru: `/wali-kelas/{id}/siswa`). Karena beberapa rombel bisa punya **nama route yang sama** (`wali.kelas.siswa`), pencocokan lewat `slug` akan salah — semua rombel ikut ter-highlight bersamaan. `url_check` membandingkan **path lengkap** (termasuk ID), bukan nama route, sehingga hanya rombel yang benar-benar sedang dibuka yang ter-highlight.

```json
{ "url": "/wali-kelas/5/siswa", "url_check": "/wali-kelas/5/siswa", "slug": "wali.kelas.siswa" }
```

### 5.4. Urutan logic penentuan active-class (parent dengan submenu)

1. Cek submenu dulu (lewat `url_check` kalau ada, atau `slug` dengan prefix match) → kalau ketemu, class = `active open`.
2. Kalau tidak ada submenu yang cocok **dan** tidak ada submenu berjenis dinamis (`url_check`) → baru cek `slug` milik parent sendiri sebagai fallback.
3. Kalau parent **tidak punya `submenu` sama sekali** → cek `slug` miliknya sendiri langsung, class = `active` (tanpa `open`, karena tidak ada yang perlu di-expand).

> ⚠️ Semua akses ke `$menu->slug` / `$sub->slug` harus dibungkus `isset()` — karena `slug` bersifat opsional (terutama untuk parent yang mengandalkan submenu sepenuhnya untuk deteksi active-nya).

## 6. Menu Dinamis — Rombel untuk Guru

Selain filtering fitur/role, `MenuServiceProvider` juga **meng-inject** menu tambahan yang tidak ada di file JSON: daftar rombel yang diampu guru yang sedang login, untuk semester yang aktif.

```php
if (Auth::user()->hasRole('Guru') && Auth::user()->ptk) {
    $semesterId = Session::get('semester_id');
    if ($semesterId) {
        $rombels = Rombel::where('ptk_id', Auth::user()->ptk->id)
            ->where('semester_id', $semesterId)
            ->get();

        if ($rombels->isNotEmpty()) {
            $verticalMenuData->menu = $this->injectMenuRombel($verticalMenuData->menu, $rombels);
        }
    }
}
```

`injectMenuRombel()` mencari section dengan `menuHeader === 'Wali Kelas'` di JSON, lalu menyisipkan satu item menu per rombel tepat setelah header itu, masing-masing dengan submenu "Data Siswa" dan "Absensi" yang URL-nya mengandung ID rombel (dan memakai `url_check` seperti dijelaskan di 5.3).

> **Urutan penting**: `filterMenu()` (fitur + role) dipanggil **sebelum** `injectMenuRombel()`. Menu rombel yang di-inject belakangan tidak ikut difilter ulang — ini disengaja, karena kemunculannya sudah pasti relevan untuk guru yang login (tidak butuh filter fitur/role tambahan).

## 7. Alur Render Lengkap

```
View::composer('*') dipanggil di setiap request (kalau user login)
        │
        ▼
Baca verticalMenu.json & horizontalMenu.json
        │
        ▼
filterMenu()  ──▶  cek fitur (FiturHelper::punya) + role (hasAnyRole)
        │              rekursif ke submenu, sembunyikan header kosong
        ▼
injectMenuRombel()  ──▶  hanya untuk role Guru, tambah menu rombel aktif
        │
        ▼
$menuData dikirim ke semua view
        │
        ▼
verticalMenu.blade.php  ──▶  render header/menu, hitung active-class
        │
        ▼
submenu.blade.php (rekursif, di-include untuk tiap item yang punya submenu)
```

## 8. Menambah Item Menu Baru — Panduan Singkat

1. Tambahkan entri baru di `verticalMenu.json` (dan `horizontalMenu.json` kalau perlu tampil juga di navbar atas).
2. Tentukan apakah butuh dikelompokkan dalam `submenu` yang sudah ada, atau berdiri sendiri.
3. Isi `slug` dengan **prefix nama route** (bukan nama route lengkap kalau ada banyak sub-route), contoh: kalau route grup pakai `->name('ptk.')`, isi `slug: "ptk."`.
4. Isi `roles` sesuai role yang boleh mengakses (kosongkan kalau untuk semua role).
5. Isi `fitur` **hanya jika** menu ini terkait fitur yang bisa di-toggle per sekolah (lihat `FITUR.md`).
6. Kalau menu ini masuk dalam grup `submenu`, pastikan `roles` di level **parent** mencakup union seluruh role anak-anaknya.

## 9. Checklist Debugging Jika Menu Tidak Muncul/Tidak Ter-highlight

- [ ] Cek apakah `roles`/`fitur` di JSON sudah sesuai dengan role user & status fitur sekolah saat ini.
- [ ] Kalau menu di dalam `submenu` tidak ter-highlight sama sekali → cek apakah `submenu.blade.php` masih pakai **exact match** (`===`) untuk slug — seharusnya prefix match seperti level atas.
- [ ] Kalau **submenu** ter-highlight tapi **parent tidak ikut expand** → cek blok `elseif (isset($menu->submenu))` di `verticalMenu.blade.php`, pastikan juga prefix match, bukan `$currentRouteName === $sub->slug`.
- [ ] Kalau muncul error `Undefined property: stdClass::$slug` → pastikan semua akses ke `->slug` dibungkus `isset()`, karena parent yang mengandalkan submenu untuk active-state tidak wajib punya `slug` sendiri.
- [ ] Kalau menu rombel (Guru) tidak muncul → cek `Session::get('semester_id')` sudah ter-set, dan pastikan ada data `Rombel` untuk `ptk_id` + `semester_id` yang sesuai.
