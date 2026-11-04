@extends('layouts/layoutFront')

@php
  $configData = Helper::appClasses();
@endphp

@section('title', 'Cek SKHU — Surat Keterangan Hasil Ujian')

@section('page-style')
  <style>
    /* ── Google Font ─────────────────────────────────────── */
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap');

    :root {
      --skhu-primary: #1a56a4;
      --skhu-primary-lt: #e8f0fb;
      --skhu-accent: #f59e0b;
      --skhu-dark: #0f2444;
      --skhu-muted: #64748b;
      --skhu-border: #e2e8f0;
      --skhu-success: #059669;
      --skhu-card-bg: #ffffff;
      --skhu-body-bg: #f1f5f9;

      /* tambahan — sebelumnya hardcoded */
      --skhu-input-bg: #f8fafc;
      --skhu-text-body: #334155;
      --skhu-placeholder: #b0bec5;
      --skhu-toggle-bg: #eef2f7;
      --skhu-error-bg: #fef2f2;
      --skhu-error-text: #dc2626;
      --skhu-white: #ffffff;
    }

    [data-bs-theme="dark"] {
      --skhu-primary: #4d8fe0;
      --skhu-primary-lt: #1a2f4d;
      --skhu-accent: #fbbf24;
      --skhu-dark: #e2e8f0;
      /* dibalik: teks jadi terang */
      --skhu-muted: #94a3b8;
      --skhu-border: #334155;
      --skhu-success: #10b981;
      --skhu-card-bg: #1e293b;
      --skhu-body-bg: #0f172a;

      --skhu-input-bg: #0f172a;
      --skhu-text-body: #cbd5e1;
      --skhu-placeholder: #64748b;
      --skhu-toggle-bg: #0f172a;
      --skhu-error-bg: #3b1414;
      --skhu-error-text: #f87171;
      --skhu-white: #1e293b;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* ── Hero ────────────────────────────────────────────── */
    .skhu-hero {
      background: linear-gradient(135deg, var(--skhu-dark) 0%, #1a4080 60%, #1a56a4 100%);
      position: relative;
      padding: 5rem 1rem 8rem;
      overflow: hidden;
    }

    .skhu-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        radial-gradient(circle at 20% 50%, rgba(245, 158, 11, .12) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(26, 86, 164, .3) 0%, transparent 40%);
    }

    .skhu-hero::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      right: 0;
      height: 80px;
      background: var(--skhu-body-bg);
      clip-path: ellipse(55% 100% at 50% 100%);
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      background: rgba(255, 255, 255, .12);
      border: 1px solid rgba(255, 255, 255, .2);
      color: #fff;
      font-size: .75rem;
      font-weight: 600;
      letter-spacing: .08em;
      text-transform: uppercase;
      padding: .35rem .85rem;
      border-radius: 999px;
      margin-bottom: 1.25rem;
      backdrop-filter: blur(8px);
    }

    .hero-badge .dot {
      width: 6px;
      height: 6px;
      background: var(--skhu-accent);
      border-radius: 50%;
      animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {

      0%,
      100% {
        opacity: 1;
        transform: scale(1);
      }

      50% {
        opacity: .5;
        transform: scale(1.4);
      }
    }

    .hero-title {
      font-family: 'DM Serif Display', serif;
      font-size: clamp(2rem, 5vw, 3.25rem);
      color: #fff;
      line-height: 1.15;
      margin-bottom: 1rem;
    }

    .hero-title em {
      color: var(--skhu-accent);
      font-style: italic;
    }

    .hero-subtitle {
      color: rgba(255, 255, 255, .72);
      font-size: 1rem;
      max-width: 500px;
      margin: 0 auto 2.5rem;
      line-height: 1.7;
    }

    /* ── Search Box ──────────────────────────────────────── */
    .search-card {
      background: #fff;
      border-radius: 1.25rem;
      padding: 2rem;
      box-shadow: 0 20px 60px rgba(15, 36, 68, .18), 0 4px 16px rgba(15, 36, 68, .08);
      max-width: 560px;
      margin: 0 auto;
      position: relative;
    }

    .search-label {
      font-size: .8rem;
      font-weight: 700;
      letter-spacing: .06em;
      color: var(--skhu-muted);
      margin-bottom: 1rem;
      display: block;
    }

    .search-input-wrap {
      position: relative;
      margin-bottom: 1rem;
    }

    .search-input-wrap .input-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--skhu-muted);
      font-size: 1.1rem;
      pointer-events: none;
    }

    .search-input {
      width: 100%;
      border: 2px solid var(--skhu-border);
      border-radius: .75rem;
      padding: .85rem 1rem .85rem 2.75rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1rem;
      font-weight: 600;
      letter-spacing: .08em;
      color: var(--skhu-dark);
      transition: border-color .2s, box-shadow .2s;
      /* background: #f8fafc; */
      background: var(--skhu-input-bg);
      outline: none;
    }

    .search-input:focus {
      border-color: var(--skhu-primary);
      box-shadow: 0 0 0 4px rgba(26, 86, 164, .1);
      background: #fff;
    }

    .search-input::placeholder {
      /* color: #b0bec5; */
      color: var(--skhu-placeholder);
      font-weight: 400;
      letter-spacing: 0;
    }

    .btn-cari {
      width: 100%;
      background: var(--skhu-primary);
      color: #fff;
      border: none;
      border-radius: .75rem;
      padding: .9rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .95rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      transition: background .2s, transform .15s, box-shadow .2s;
      box-shadow: 0 4px 14px rgba(26, 86, 164, .35);
    }

    .btn-cari:hover {
      background: #144689;
      box-shadow: 0 6px 20px rgba(26, 86, 164, .45);
      transform: translateY(-1px);
    }

    .btn-cari:active {
      transform: translateY(0);
    }

    .btn-cari:disabled {
      background: #94a3b8;
      box-shadow: none;
      cursor: not-allowed;
      transform: none;
    }

    .search-note {
      text-align: center;
      font-size: .78rem;
      color: var(--skhu-muted);
      margin-top: .75rem;
    }

    /* ── Main content area ───────────────────────────────── */
    .skhu-main {
      background: var(--skhu-body-bg);
      min-height: 60vh;
      padding: 2.5rem 1rem 4rem;
    }

    /* ── Result wrapper ──────────────────────────────────── */
    #result-wrapper {
      max-width: 800px;
      margin: 0 auto;
    }

    /* ── State cards ─────────────────────────────────────── */
    .state-card {
      background: #fff;
      border-radius: 1rem;
      padding: 3rem 2rem;
      text-align: center;
      box-shadow: 0 2px 12px rgba(15, 36, 68, .07);
    }

    .state-icon {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.25rem;
      font-size: 2rem;
    }

    .state-icon.info {
      background: var(--skhu-primary-lt);
      color: var(--skhu-primary);
    }

    .state-icon.error {
      /* background: #fef2f2;
                        color: #dc2626; */
      background: var(--skhu-error-bg);
      color: var(--skhu-error-text);
    }

    .state-icon.loading {
      background: #f0fdf4;
      color: var(--skhu-success);
    }

    /* ── Siswa identity card ─────────────────────────────── */
    .siswa-card {
      background: linear-gradient(135deg, var(--skhu-dark) 0%, #1a56a4 100%);
      border-radius: 1rem;
      padding: 1.75rem 2rem;
      color: #fff;
      margin-bottom: 1.5rem;
      position: relative;
      overflow: hidden;
    }

    .siswa-card::after {
      content: '';
      position: absolute;
      right: -30px;
      top: -30px;
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .06);
    }

    .siswa-card .verified-badge {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      background: rgba(5, 150, 105, .9);
      color: #fff;
      font-size: .7rem;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      padding: .3rem .7rem;
      border-radius: 999px;
      margin-bottom: .85rem;
    }

    .siswa-card .nama {
      font-family: 'DM Serif Display', serif;
      font-size: 1.6rem;
      margin-bottom: .25rem;
    }

    .siswa-card .nisn-text {
      font-size: .85rem;
      opacity: .75;
      font-weight: 600;
      letter-spacing: .06em;
    }

    .siswa-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(255, 255, 255, .15);
    }

    .siswa-meta-item {
      font-size: .82rem;
      opacity: .8;
    }

    .siswa-meta-item strong {
      display: block;
      opacity: 1;
      font-size: .78rem;
      letter-spacing: .04em;
      text-transform: uppercase;
      opacity: .55;
      margin-bottom: .15rem;
    }

    /* ── SKHU detail card ────────────────────────────────── */
    .skhu-card {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 2px 12px rgba(15, 36, 68, .07);
      margin-bottom: 1.5rem;
      overflow: hidden;
    }

    .skhu-card-header {
      background: #f8fafc;
      border-bottom: 2px solid var(--skhu-border);
      padding: 1.25rem 1.75rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: .75rem;
    }

    .skhu-jenjang-badge {
      background: var(--skhu-primary-lt);
      color: var(--skhu-primary);
      font-weight: 800;
      font-size: .8rem;
      letter-spacing: .06em;
      text-transform: uppercase;
      padding: .35rem .85rem;
      border-radius: .5rem;
    }

    .skhu-tahun {
      font-size: .85rem;
      color: var(--skhu-muted);
      font-weight: 600;
    }

    .skhu-card-meta {
      padding: 1.25rem 1.75rem;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 1rem;
      border-bottom: 1px solid var(--skhu-border);
    }

    .meta-item label {
      display: block;
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      color: var(--skhu-muted);
      margin-bottom: .2rem;
    }

    .meta-item span {
      font-size: .88rem;
      font-weight: 600;
      color: var(--skhu-dark);
    }

    /* ── Type toggle (SKHU / SKL) ─────────────────────────── */
    .type-toggle {
      display: flex;
      /* background: #eef2f7; */
      background: var(--skhu-toggle-bg);
      border-radius: .75rem;
      padding: .3rem;
      margin-bottom: 1.5rem;
      gap: .3rem;
    }

    .type-toggle-btn {
      flex: 1;
      border: none;
      background: transparent;
      padding: .65rem 1rem;
      border-radius: .55rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .85rem;
      font-weight: 700;
      color: var(--skhu-muted);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .45rem;
      transition: all .2s;
    }

    .type-toggle-btn.active {
      background: #fff;
      color: var(--skhu-primary);
      box-shadow: 0 2px 8px rgba(15, 36, 68, .1);
    }

    .type-toggle-btn:disabled {
      opacity: .4;
      cursor: not-allowed;
    }

    .panel-empty {
      padding: 2.5rem 2rem;
    }

    .panel-empty .state-icon {
      width: 56px;
      height: 56px;
      font-size: 1.5rem;
    }

    /* ── Nilai table ─────────────────────────────────────── */
    .nilai-section {
      padding: 1.5rem 1.75rem;
    }

    .nilai-section h6 {
      font-size: .78rem;
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--skhu-muted);
      margin-bottom: 1rem;
    }

    .kelompok-block {
      margin-bottom: 1.25rem;
    }

    .kelompok-label {
      font-size: .78rem;
      font-weight: 700;
      color: var(--skhu-primary);
      letter-spacing: .04em;
      padding: .4rem .75rem;
      background: var(--skhu-primary-lt);
      border-radius: .4rem;
      margin-bottom: .5rem;
      display: block;
    }

    .nilai-row {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      padding: .5rem .75rem;
      border-radius: .4rem;
      transition: background .15s;
    }

    .nilai-row:hover {
      background: #f8fafc;
    }

    .nilai-row .mapel-nama {
      font-size: .875rem;
      /* color: #334155; */
      color: var(--skhu-text-body);
      flex: 1;
    }

    .nilai-row .mapel-sub {
      font-size: .78rem;
      color: var(--skhu-muted);
      display: block;
      margin-top: .1rem;
    }

    .nilai-angka {
      font-size: .9rem;
      font-weight: 800;
      color: var(--skhu-dark);
      min-width: 48px;
      text-align: right;
      font-variant-numeric: tabular-nums;
    }

    /* Color scale */
    .nilai-angka.tinggi {
      color: var(--skhu-success);
    }

    .nilai-angka.sedang {
      color: #d97706;
    }

    .nilai-angka.rendah {
      color: #dc2626;
    }

    .rata-rata-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: .85rem 1rem;
      background: var(--skhu-dark);
      color: #fff;
      border-radius: .6rem;
      margin-top: 1rem;
    }

    .rata-rata-row .label {
      font-size: .8rem;
      font-weight: 700;
      letter-spacing: .05em;
      text-transform: uppercase;
      opacity: .75;
    }

    .rata-rata-row .nilai {
      font-family: 'DM Serif Display', serif;
      font-size: 1.4rem;
      color: var(--skhu-accent);
    }

    /* ── Multiple SKHU tabs ──────────────────────────────── */
    .skhu-tabs {
      display: flex;
      gap: .5rem;
      margin-bottom: 1.25rem;
      flex-wrap: wrap;
    }

    .skhu-tab {
      padding: .45rem 1rem;
      border-radius: .5rem;
      border: 2px solid var(--skhu-border);
      background: #fff;
      font-size: .82rem;
      font-weight: 700;
      color: var(--skhu-muted);
      cursor: pointer;
      transition: all .2s;
    }

    .skhu-tab.active {
      border-color: var(--skhu-primary);
      background: var(--skhu-primary-lt);
      color: var(--skhu-primary);
    }

    /* ── Footer ──────────────────────────────────────────── */
    .skhu-footer {
      text-align: center;
      padding: 2rem 1rem;
      font-size: .8rem;
      color: var(--skhu-muted);
      background: var(--skhu-body-bg);
    }

    /* ── Spinner ─────────────────────────────────────────── */
    .spinner {
      width: 36px;
      height: 36px;
      border: 3px solid var(--skhu-border);
      border-top-color: var(--skhu-primary);
      border-radius: 50%;
      animation: spin .7s linear infinite;
      margin: 0 auto 1rem;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    /* ── Fade in ─────────────────────────────────────────── */
    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(16px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .fade-up {
      animation: fadeUp .4s ease both;
    }

    .fade-up-delay {
      animation: fadeUp .4s ease .15s both;
    }

    .skhu-card,
    .skhu-tab,
    #state-default,
    #state-loading,
    #state-error {
      background: var(--skhu-card-bg);
      /* pastikan semua card pakai ini, bukan #fff langsung */
    }
  </style>
@endsection

@section('content')

  {{-- ═══ HERO ════════════════════════════════════════════════════════════ --}}
  <section class="skhu-hero section-py first-section-pt text-center position-relative overflow-hidden">
    <div class="position-relative">
      <div class="hero-badge">
        <span class="dot"></span>
        Layanan Verifikasi Data Kelulusan
      </div>
      <h1 class="hero-title">
        Cek <em>SKHU</em> &amp; <em>SKL</em><br>
      </h1>
      {{-- <p class="hero-subtitle"> --}}
      {{-- </p> --}}

      {{-- Search Card --}}
      <div class="search-card fade-up">
        <label class="search-label" for="input-nisn">
          <i class="ri ri-search-line me-1"></i> Masukkan NISN untuk melihat data SKHU &amp; SKL
        </label>
        <div class="search-input-wrap">
          <i class="ri ri-user-line input-icon"></i>
          <input type="text" id="input-nisn" class="search-input" placeholder="Contoh: 0012345678" maxlength="10"
            inputmode="numeric" pattern="\d{10}" autocomplete="off" spellcheck="false">
        </div>
        <button class="btn-cari" id="btn-cari" type="button">
          <i class="ri ri-search-2-line"></i>
          Cari Data SKHU &amp; SKL
        </button>
        <p class="search-note">
          <i class="ri ri-lock-line me-1"></i>
          SKHU ditampilkan jika sudah final, SKL ditampilkan jika sudah disetujui.
        </p>
      </div>
    </div>
  </section>

  {{-- ═══ MAIN RESULT AREA ══════════════════════════════════════════════ --}}
  <section class="skhu-main">
    <div id="result-wrapper">

      {{-- Default state --}}
      <div id="state-default" class="state-card fade-up-delay">
        <div class="state-icon info">
          <i class="ri ri-file-search-line"></i>
        </div>
        <h5 style="font-family:'DM Serif Display',serif; color:var(--skhu-dark); margin-bottom:.5rem">
          Masukkan NISN untuk mulai
        </h5>
        <p style="color:var(--skhu-muted); font-size:.9rem; max-width:380px; margin:0 auto">
          Hasil pencarian akan tampil di sini. Pastikan NISN yang Anda masukkan terdiri dari 10 digit angka.
        </p>
      </div>

      {{-- Loading state --}}
      <div id="state-loading" class="state-card" style="display:none">
        <div class="spinner"></div>
        <p style="color:var(--skhu-muted); font-size:.9rem; margin:0">Sedang mengambil data…</p>
      </div>

      {{-- Error state --}}
      <div id="state-error" class="state-card" style="display:none">
        <div class="state-icon error">
          <i class="ri ri-error-warning-line"></i>
        </div>
        <h5 id="error-title" style="color:var(--skhu-dark); margin-bottom:.5rem">Data tidak ditemukan</h5>
        <p id="error-msg" style="color:var(--skhu-muted); font-size:.9rem; max-width:380px; margin:0 auto"></p>
      </div>

      {{-- Result state --}}
      <div id="state-result" style="display:none">

        {{-- Identitas siswa --}}
        <div id="siswa-card" class="siswa-card fade-up">
          <span class="verified-badge"><i class="ri ri-verified-badge-line"></i> Terverifikasi</span>
          <div class="nama" id="res-nama">—</div>
          <div class="nisn-text">NISN: <span id="res-nisn">—</span></div>
          <div class="siswa-meta">
            <div class="siswa-meta-item">
              <strong>Tempat Lahir</strong>
              <span id="res-tempat-lahir">—</span>
            </div>
            <div class="siswa-meta-item">
              <strong>Tanggal Lahir</strong>
              <span id="res-tgl-lahir">—</span>
            </div>
            <div class="siswa-meta-item">
              <strong>Nama Ayah</strong>
              <span id="res-nama-ayah">—</span>
            </div>
          </div>
        </div>

        {{-- Toggle SKHU / SKL --}}
        <div class="type-toggle fade-up">
          <button class="type-toggle-btn active" data-type="skhu" type="button">
            <i class="ri ri-file-list-3-line"></i> SKHU
          </button>
          <button class="type-toggle-btn" data-type="skl" type="button">
            <i class="ri ri-award-line"></i> SKL
          </button>
        </div>

        {{-- Panel SKHU --}}
        <div id="panel-skhu">
          <div id="skhu-tabs-wrapper" class="skhu-tabs" style="display:none"></div>
          <div id="skhu-detail-wrapper"></div>
          <div id="skhu-empty" class="state-card panel-empty" style="display:none">
            <div class="state-icon info"><i class="ri ri-file-search-line"></i></div>
            <h5 style="color:var(--skhu-dark); margin-bottom:.4rem">Belum Ada Data SKHU</h5>
            <p style="color:var(--skhu-muted); font-size:.88rem; margin:0">
              SKHU final untuk NISN ini belum tersedia.
            </p>
          </div>
        </div>

        {{-- Panel SKL --}}
        <div id="panel-skl" style="display:none">
          <div id="skl-tabs-wrapper" class="skhu-tabs" style="display:none"></div>
          <div id="skl-detail-wrapper"></div>
          <div id="skl-empty" class="state-card panel-empty" style="display:none">
            <div class="state-icon info"><i class="ri ri-file-search-line"></i></div>
            <h5 style="color:var(--skhu-dark); margin-bottom:.4rem">Belum Ada Data SKL</h5>
            <p style="color:var(--skhu-muted); font-size:.88rem; margin:0">
              SKL yang sudah disetujui untuk NISN ini belum tersedia.
            </p>
          </div>
        </div>

      </div>
    </div>
  </section>

  {{-- ═══ FOOTER MINI ═════════════════════════════════════════════════════ --}}
  <div class="skhu-footer">
    Data SKHU bersifat resmi dan hanya dapat diakses untuk keperluan verifikasi.<br>
    Jika terdapat kekeliruan data, hubungi pihak sekolah terkait.
  </div>

@endsection

@section('page-script')
  <script>
    (function() {
      const inputNisn = document.getElementById('input-nisn');
      const btnCari = document.getElementById('btn-cari');

      const stateDefault = document.getElementById('state-default');
      const stateLoading = document.getElementById('state-loading');
      const stateError = document.getElementById('state-error');
      const stateResult = document.getElementById('state-result');

      const errorTitle = document.getElementById('error-title');
      const errorMsg = document.getElementById('error-msg');

      // ── Hanya angka ──────────────────────────────────────────────────────
      inputNisn.addEventListener('input', () => {
        inputNisn.value = inputNisn.value.replace(/\D/g, '').slice(0, 10);
      });

      // ── Enter trigger ─────────────────────────────────────────────────────
      inputNisn.addEventListener('keydown', e => {
        if (e.key === 'Enter') btnCari.click();
      });

      // ── Toggle states ─────────────────────────────────────────────────────
      function showState(name) {
        [stateDefault, stateLoading, stateError, stateResult].forEach(el => {
          el.style.display = 'none';
        });
        document.getElementById('state-' + name).style.display = 'block';
      }

      // ── Render warna nilai ────────────────────────────────────────────────
      function nilaiClass(n) {
        const v = parseFloat(n);
        if (v >= 75) return 'tinggi';
        if (v >= 55) return 'sedang';
        return 'rendah';
      }

      // ── Render satu SKHU ──────────────────────────────────────────────────
      function renderSkhu(skhu) {
        const nilaiHtml = skhu.nilai_per_kelompok.map(grp => `
        <div class="kelompok-block">
          <span class="kelompok-label">${grp.kelompok}</span>
          ${grp.mapels.map(m => `
                                                                        <div class="nilai-row">
                                                                          <div class="mapel-nama">
                                                                            ${m.nama_mapel}
                                                                            ${m.nama_mapel_sub ? `<span class="mapel-sub">${m.nama_mapel_sub}</span>` : ''}
                                                                          </div>
                                                                          <span class="nilai-angka ${nilaiClass(m.nilai)}">${m.nilai}</span>
                                                                        </div>
                                                                      `).join('')}
        </div>
      `).join('');

        return `
        <div class="skhu-card fade-up">
          <div class="skhu-card-header">
            <div>
              <span class="skhu-jenjang-badge">${skhu.sekolah.jenjang}</span>
              <span class="skhu-tahun ms-2">${skhu.tahun_pelajaran}</span>
            </div>
            <div style="font-size:.8rem; color:var(--skhu-muted); text-align:right">
              Terbit: <strong style="color:var(--skhu-dark)">${skhu.tanggal_terbit ?? '—'}</strong>
            </div>
          </div>

          <div class="skhu-card-meta">
            <div class="meta-item">
              <label>Nama Sekolah</label>
              <span>${skhu.sekolah.nama}</span>
            </div>
            <div class="meta-item">
              <label>NPSN</label>
              <span>${skhu.sekolah.npsn}</span>
            </div>
            <div class="meta-item">
              <label>Kepala Sekolah</label>
              <span>${skhu.sekolah.kepala_sekolah}</span>
            </div>
            <div class="meta-item">
              <label>Nomor Surat</label>
              <span>${skhu.nomor_surat ?? '—'}</span>
            </div>
            <div class="meta-item">
              <label>Nomor Ujian</label>
              <span>${skhu.nomor_ujian ?? '—'}</span>
            </div>
            <div class="meta-item">
              <label>Periode Ujian</label>
              <span>${skhu.tanggal_mulai_ujian ?? '—'}${skhu.tanggal_selesai_ujian ? ' s.d. ' + skhu.tanggal_selesai_ujian : ''}</span>
            </div>
          </div>

          <div class="nilai-section">
            <h6><i class="ri ri-list-check me-1"></i>Nilai Ujian Sekolah</h6>
            ${nilaiHtml}
            <div class="rata-rata-row">
              <span class="label">Rata-Rata Nilai</span>
              <span class="nilai">${parseFloat(skhu.nilai_rata_rata).toFixed(2)}</span>
            </div>
          </div>
        </div>
      `;
      }

      // ── Render satu SKL ─────────────────────────────────────────────────────
      function renderSkl(skl) {
        const nilaiHtml = skl.nilai_per_kelompok.map(grp => `
        <div class="kelompok-block">
          <span class="kelompok-label">${grp.kelompok}</span>
          ${grp.mapels.map(m => `
                                                                        <div class="nilai-row">
                                                                          <div class="mapel-nama">
                                                                            ${m.nama_mapel}
                                                                            ${m.nama_mapel_sub ? `<span class="mapel-sub">${m.nama_mapel_sub}</span>` : ''}
                                                                          </div>
                                                                          <span class="nilai-angka ${m.nilai !== null ? nilaiClass(m.nilai) : ''}">
                                                                            ${m.nilai ?? '—'}${m.nilai_huruf ? ` <small style="font-weight:600; opacity:.6">(${m.nilai_huruf})</small>` : ''}
                                                                          </span>
                                                                        </div>
                                                                      `).join('')}
        </div>
      `).join('');

        return `
        <div class="skhu-card fade-up">
          <div class="skhu-card-header">
            <div>
              <span class="skhu-jenjang-badge">${skl.sekolah.jenjang}</span>
              <span class="skhu-tahun ms-2">${skl.tahun_pelajaran}</span>
            </div>
            <div style="font-size:.8rem; color:var(--skhu-muted); text-align:right">
              Terbit: <strong style="color:var(--skhu-dark)">${skl.tanggal_terbit ?? '—'}</strong>
            </div>
          </div>

          <div class="skhu-card-meta">
            <div class="meta-item">
              <label>Nama Sekolah</label>
              <span>${skl.sekolah.nama}</span>
            </div>
            <div class="meta-item">
              <label>NPSN</label>
              <span>${skl.sekolah.npsn}</span>
            </div>
            <div class="meta-item">
              <label>Kepala Sekolah</label>
              <span>${skl.sekolah.kepala_sekolah}</span>
            </div>
            <div class="meta-item">
              <label>Nomor SKL</label>
              <span>${skl.nomor_skl ?? '—'}</span>
            </div>
          </div>

          <div class="nilai-section">
            <h6><i class="ri ri-list-check me-1"></i>Nilai SKL</h6>
            ${nilaiHtml}
            <div class="rata-rata-row">
              <span class="label">Rata-Rata Nilai</span>
              <span class="nilai">${parseFloat(skl.nilai_rata_rata).toFixed(2)}</span>
            </div>
          </div>
        </div>
      `;
      }

      // ── Render panel generik (SKHU atau SKL) ────────────────────────────────
      function renderPanel(type, list, renderFn) {
        const tabsWrapper = document.getElementById(`${type}-tabs-wrapper`);
        const detailWrapper = document.getElementById(`${type}-detail-wrapper`);
        const emptyState = document.getElementById(`${type}-empty`);

        tabsWrapper.innerHTML = '';
        detailWrapper.innerHTML = '';

        if (!list || !list.length) {
          tabsWrapper.style.display = 'none';
          detailWrapper.style.display = 'none';
          emptyState.style.display = 'block';
          return;
        }

        emptyState.style.display = 'none';
        detailWrapper.style.display = 'block';

        if (list.length === 1) {
          tabsWrapper.style.display = 'none';
          detailWrapper.innerHTML = renderFn(list[0]);
        } else {
          tabsWrapper.style.display = 'flex';

          list.forEach((item, i) => {
            const tab = document.createElement('button');
            tab.className = 'skhu-tab' + (i === 0 ? ' active' : '');
            tab.textContent = `${item.sekolah.jenjang} — ${item.tahun_pelajaran}`;
            tab.dataset.idx = i;
            tab.addEventListener('click', () => {
              tabsWrapper.querySelectorAll('.skhu-tab').forEach(t => t.classList.remove('active'));
              tab.classList.add('active');
              detailWrapper.innerHTML = renderFn(item);
            });
            tabsWrapper.appendChild(tab);
          });

          detailWrapper.innerHTML = renderFn(list[0]);
        }
      }

      // ── Toggle panel SKHU / SKL ─────────────────────────────────────────────
      const panelSkhu = document.getElementById('panel-skhu');
      const panelSkl = document.getElementById('panel-skl');
      const toggleBtns = document.querySelectorAll('.type-toggle-btn');

      function activatePanel(type) {
        toggleBtns.forEach(btn => btn.classList.toggle('active', btn.dataset.type === type));
        panelSkhu.style.display = type === 'skhu' ? 'block' : 'none';
        panelSkl.style.display = type === 'skl' ? 'block' : 'none';
      }

      toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          if (btn.disabled) return;
          activatePanel(btn.dataset.type);
        });
      });

      // ── Cari ──────────────────────────────────────────────────────────────
      btnCari.addEventListener('click', async () => {
        const nisn = inputNisn.value.trim();

        if (!nisn || nisn.length !== 10) {
          inputNisn.focus();
          inputNisn.style.borderColor = '#dc2626';
          inputNisn.style.boxShadow = '0 0 0 4px rgba(220,38,38,.1)';
          setTimeout(() => {
            inputNisn.style.borderColor = '';
            inputNisn.style.boxShadow = '';
          }, 2000);
          return;
        }

        showState('loading');
        btnCari.disabled = true;

        try {
          const fetchJson = async (url) => {
            const r = await fetch(url);
            const data = await r.json();
            return {
              ok: r.ok,
              status: r.status,
              data
            };
          };

          const [skhuRes, sklRes] = await Promise.all([
            fetchJson(`/api/validasi-skhu/${nisn}`),
            fetchJson(`/api/validasi-skl/${nisn}`),
          ]);

          const skhuOk = skhuRes.ok && skhuRes.data.skhu_list && skhuRes.data.skhu_list.length;
          const sklOk = sklRes.ok && sklRes.data.skl_list && sklRes.data.skl_list.length;

          // ── Keduanya tidak ada data ────────────────────────────────────────
          if (!skhuOk && !sklOk) {
            // Prioritaskan pesan dari respons yang bukan 404 generik (mis. NISN tidak valid)
            const errSource = skhuRes.status !== 404 ? skhuRes : sklRes;
            errorTitle.textContent = errSource.status === 404 ? 'Data Tidak Ditemukan' : 'Terjadi Kesalahan';
            errorMsg.textContent = errSource.data.message ?? 'Silakan coba beberapa saat lagi.';
            showState('error');
            return;
          }

          // ── Isi identitas siswa (ambil dari salah satu yang berhasil) ────────
          const siswa = skhuOk ? skhuRes.data.siswa : sklRes.data.siswa;
          document.getElementById('res-nama').textContent = siswa.nama;
          document.getElementById('res-nisn').textContent = siswa.nisn;
          document.getElementById('res-tempat-lahir').textContent = siswa.tempat_lahir ?? '—';
          document.getElementById('res-tgl-lahir').textContent = siswa.tanggal_lahir ?? '—';
          document.getElementById('res-nama-ayah').textContent = siswa.nama_ayah ?? '—';

          // ── Render kedua panel ────────────────────────────────────────────
          renderPanel('skhu', skhuOk ? skhuRes.data.skhu_list : [], renderSkhu);
          renderPanel('skl', sklOk ? sklRes.data.skl_list : [], renderSkl);

          // ── Aktifkan/nonaktifkan tombol toggle sesuai ketersediaan data ────
          toggleBtns.forEach(btn => {
            const available = btn.dataset.type === 'skhu' ? skhuOk : sklOk;
            btn.disabled = !available;
          });
          activatePanel(skhuOk ? 'skhu' : 'skl');

          showState('result');

          // Scroll ke hasil
          document.getElementById('state-result').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });

        } catch (err) {
          errorTitle.textContent = 'Gagal Menghubungi Server';
          errorMsg.textContent = 'Periksa koneksi internet Anda dan coba lagi.';
          showState('error');
        } finally {
          btnCari.disabled = false;
        }
      });
    })();
  </script>
@endsection
