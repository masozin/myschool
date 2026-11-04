{{--
  ===========================================================================
  SHARED DESIGN TOKENS — Ruang Belajar
  ===========================================================================
  Include SEKALI saja di layout terluar (commonMaster), di dalam <head>,
  SEBELUM @yield('content'). Semua partial slide (intro, timeline, article,
  model3d, dst) tinggal pakai var(--lp-...) — jangan tulis hex langsung lagi
  di partial manapun. Ganti tema = ganti atribut data-theme di <html>.
--}}
<style>
  /* ===== DEFAULT THEME: "tech" ===== */
  :root,
  [data-theme="tech"] {
    --lp-bg: #F5F6FA;
    --lp-panel: #FFFFFF;
    --lp-stage-1: #EFF1F7;
    --lp-stage-2: #E6E9F2;
    --lp-ink: #1C2333;
    --lp-ink-soft: #5B6478;
    --lp-line: #E3E6EE;
    --lp-accent: #0EA5A4;
    --lp-accent-soft: #E3F7F6;
    --lp-highlight: #F5A623;
    --lp-highlight-soft: #FEF3DE;
    --lp-radius: 16px;
    --lp-font-display: 'Fraunces', serif;
    --lp-font-body: 'Public Sans', 'Inter', sans-serif;
    --lp-font-mono: 'JetBrains Mono', monospace;
    --lp-grid-line: rgba(28, 35, 51, .05);
  }

  /* ===== DARK ===== */
  [data-theme="dark"] {
    --lp-bg: #0F1420;
    --lp-panel: #171D2C;
    --lp-stage-1: #141A28;
    --lp-stage-2: #0D1220;
    --lp-ink: #EDEFF5;
    --lp-ink-soft: #9AA3B8;
    --lp-line: #2A3244;
    --lp-accent: #4CC9C0;
    --lp-accent-soft: rgba(76, 201, 192, .14);
    --lp-highlight: #F2B84B;
    --lp-highlight-soft: rgba(242, 184, 75, .14);
    --lp-radius: 16px;
    --lp-font-display: 'Fraunces', serif;
    --lp-font-body: 'Public Sans', 'Inter', sans-serif;
    --lp-font-mono: 'JetBrains Mono', monospace;
    --lp-grid-line: rgba(255, 255, 255, .05);
  }

  /* ===== ASTRONAUT (ruang angkasa) ===== */
  [data-theme="astronaut"] {
    --lp-bg: #0B0E1A;
    --lp-panel: #131730;
    --lp-stage-1: #10142A;
    --lp-stage-2: #070812;
    --lp-ink: #EAEBFA;
    --lp-ink-soft: #8C90B8;
    --lp-line: #262C52;
    --lp-accent: #7C9CFF;
    --lp-accent-soft: rgba(124, 156, 255, .16);
    --lp-highlight: #FFC857;
    --lp-highlight-soft: rgba(255, 200, 87, .16);
    --lp-radius: 20px;
    --lp-font-display: 'Fraunces', serif;
    --lp-font-body: 'Inter', sans-serif;
    --lp-font-mono: 'JetBrains Mono', monospace;
    --lp-grid-line: rgba(124, 156, 255, .08);
  }

  /* ===== NATURAL ===== */
  [data-theme="natural"] {
    --lp-bg: #F6F4EC;
    --lp-panel: #FFFFFF;
    --lp-stage-1: #EFEDE0;
    --lp-stage-2: #E4E0CE;
    --lp-ink: #2B2A22;
    --lp-ink-soft: #6C6A57;
    --lp-line: #E3DFCC;
    --lp-accent: #5B8C5A;
    --lp-accent-soft: #E7F0E4;
    --lp-highlight: #C97A3D;
    --lp-highlight-soft: #F6E7D8;
    --lp-radius: 14px;
    --lp-font-display: 'Fraunces', serif;
    --lp-font-body: 'Public Sans', 'Inter', sans-serif;
    --lp-font-mono: 'JetBrains Mono', monospace;
    --lp-grid-line: rgba(91, 140, 90, .08);
  }

  /* Terapkan bg dasar halaman mengikuti tema aktif */
  body {
    background-color: var(--lp-bg) !important;
    color: var(--lp-ink);
    transition: background-color .25s ease, color .25s ease;
  }
</style>
