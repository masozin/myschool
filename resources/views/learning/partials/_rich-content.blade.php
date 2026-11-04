{{--
  ===========================================================================
  RICH CONTENT TYPOGRAPHY — untuk output editor Quill
  ===========================================================================
  Include SEKALI di layout (sama seperti _theme-tokens), setelah token tema.
  Lalu bungkus HTML hasil Quill dengan class "lp-rich-content" di mana pun
  ia ditampilkan:

    <div class="lp-rich-content">{!! $slide->content_text !!}</div>

  Otomatis ikut warna tema aktif karena semua pakai var(--lp-...).
  Meng-cover elemen standar Quill: bold/italic/underline/strike, warna teks
  & highlight (inline style, otomatis kebawa), list, blockquote, heading,
  link, code, DAN class khusus Quill: ql-align-*, ql-indent-*, ql-size-*,
  ql-font-*.
--}}
<style>
  .lp-rich-content {
    color: var(--lp-ink);
    font-family: var(--lp-font-body);
    font-size: .92rem;
    line-height: 1.75;
    word-wrap: break-word;
  }

  .lp-rich-content>*:first-child {
    margin-top: 0;
  }

  .lp-rich-content>*:last-child {
    margin-bottom: 0;
  }

  .lp-rich-content p {
    margin: 0 0 12px;
  }

  .lp-rich-content strong,
  .lp-rich-content b {
    font-weight: 700;
    color: var(--lp-ink);
  }

  .lp-rich-content em,
  .lp-rich-content i {
    font-style: italic;
  }

  .lp-rich-content u {
    text-decoration-color: var(--lp-accent);
    text-underline-offset: 3px;
  }

  .lp-rich-content s,
  .lp-rich-content strike {
    text-decoration-color: var(--lp-highlight);
  }

  .lp-rich-content a {
    color: var(--lp-accent);
    text-decoration: underline;
    text-underline-offset: 2px;
    font-weight: 600;
    transition: color .15s ease;
  }

  .lp-rich-content a:hover {
    color: var(--lp-highlight);
  }

  /* ---- List ---- */
  .lp-rich-content ul,
  .lp-rich-content ol {
    margin: 0 0 14px;
    padding-left: 1.35em;
  }

  .lp-rich-content li {
    margin-bottom: 6px;
  }

  .lp-rich-content ul li::marker {
    color: var(--lp-accent);
  }

  .lp-rich-content ol li::marker {
    color: var(--lp-accent);
    font-weight: 700;
  }

  .lp-rich-content ul ul,
  .lp-rich-content ol ol,
  .lp-rich-content ul ol,
  .lp-rich-content ol ul {
    margin-top: 6px;
  }

  /* ---- Heading (kalau editor Quill mengizinkan H1-H6) ---- */
  .lp-rich-content h1,
  .lp-rich-content h2,
  .lp-rich-content h3,
  .lp-rich-content h4,
  .lp-rich-content h5,
  .lp-rich-content h6 {
    font-family: var(--lp-font-display);
    color: var(--lp-ink);
    font-weight: 700;
    line-height: 1.3;
    margin: 18px 0 8px;
  }

  .lp-rich-content h1 {
    font-size: 1.35rem;
  }

  .lp-rich-content h2 {
    font-size: 1.18rem;
  }

  .lp-rich-content h3 {
    font-size: 1.05rem;
  }

  .lp-rich-content h4,
  .lp-rich-content h5,
  .lp-rich-content h6 {
    font-size: .95rem;
  }

  /* ---- Blockquote ---- */
  .lp-rich-content blockquote {
    margin: 14px 0;
    padding: 10px 16px;
    border-left: 3px solid var(--lp-accent);
    background: var(--lp-accent-soft);
    border-radius: 0 12px 12px 0;
    font-style: italic;
    color: var(--lp-ink-soft);
  }

  .lp-rich-content blockquote p {
    margin: 0;
  }

  /* ---- Code ---- */
  .lp-rich-content code {
    background: var(--lp-accent-soft);
    color: var(--lp-accent);
    padding: 2px 6px;
    border-radius: 5px;
    font-family: var(--lp-font-mono);
    font-size: .85em;
  }

  .lp-rich-content pre {
    background: var(--lp-stage-2, var(--lp-panel));
    border: 1px solid var(--lp-line);
    padding: 12px 14px;
    border-radius: 12px;
    overflow-x: auto;
    margin: 0 0 14px;
  }

  .lp-rich-content pre code {
    background: none;
    padding: 0;
    color: var(--lp-ink);
  }

  /* ---- Gambar & video yang mungkin disisipkan lewat Quill ---- */
  .lp-rich-content img {
    max-width: 100%;
    border-radius: 10px;
    margin: 8px 0;
  }

  .lp-rich-content iframe {
    max-width: 100%;
    border-radius: 10px;
    border: 1px solid var(--lp-line);
  }

  /* ---- Divider (Quill "hr" via markdown module) ---- */
  .lp-rich-content hr {
    border: none;
    border-top: 1px solid var(--lp-line);
    margin: 16px 0;
  }

  /* ================= CLASS BAWAAN QUILL ================= */
  .lp-rich-content .ql-align-center {
    text-align: center;
  }

  .lp-rich-content .ql-align-right {
    text-align: right;
  }

  .lp-rich-content .ql-align-justify {
    text-align: justify;
  }

  .lp-rich-content .ql-indent-1 {
    padding-left: 2em;
  }

  .lp-rich-content .ql-indent-2 {
    padding-left: 4em;
  }

  .lp-rich-content .ql-indent-3 {
    padding-left: 6em;
  }

  .lp-rich-content .ql-indent-4 {
    padding-left: 8em;
  }

  .lp-rich-content .ql-size-small {
    font-size: .78em;
  }

  .lp-rich-content .ql-size-large {
    font-size: 1.3em;
  }

  .lp-rich-content .ql-size-huge {
    font-size: 1.7em;
  }

  .lp-rich-content .ql-font-serif {
    font-family: Georgia, 'Times New Roman', serif;
  }

  .lp-rich-content .ql-font-monospace {
    font-family: var(--lp-font-mono);
  }

  /* Quill selalu menyisipkan background-color (sering putih/rgb(255,255,255))
     ke elemen berwarna, walau user cuma pilih "text color" — bukan
     "highlight". Ini artefak bawaan Quill (kadang juga dari hasil paste
     Word/Google Docs). Kalau tidak dinetralkan, background putih itu akan
     kelihatan seperti kotak/sorotan begitu ditaruh di atas panel yang tidak
     putih (mis. m3d-info-box yang berwarna krem/amber).

     PENTING: kombinasi format (tebal+warna, miring+warna, garis bawah+warna)
     TIDAK selalu dibungkus <span> — Quill sering menaruh style langsung di
     tag <strong>/<em>/<u>/<a> itu sendiri. Makanya semua tag ini disasar,
     bukan cuma span. Warna teks (color) tetap dipertahankan apa adanya,
     yang dipaksa transparan cuma background-color. */
  .lp-rich-content span[style],
  .lp-rich-content strong[style],
  .lp-rich-content b[style],
  .lp-rich-content em[style],
  .lp-rich-content i[style],
  .lp-rich-content u[style],
  .lp-rich-content a[style],
  .lp-rich-content s[style] {
    background-color: transparent !important;
  }

  /* Varian untuk konten yang ditaruh di atas foto/background gelap
     (mis. slide intro/cover) — teks dasar diputihkan, TAPI warna teks
     yang memang dipilih user di Quill tetap dipertahankan (inline style
     color selalu menang karena lebih spesifik). */
  .lp-rich-content.on-dark {
    color: rgba(255, 255, 255, .85);
  }

  .lp-rich-content.on-dark strong,
  .lp-rich-content.on-dark b,
  .lp-rich-content.on-dark h1,
  .lp-rich-content.on-dark h2,
  .lp-rich-content.on-dark h3,
  .lp-rich-content.on-dark h4,
  .lp-rich-content.on-dark h5,
  .lp-rich-content.on-dark h6 {
    color: #fff;
  }

  .lp-rich-content.on-dark a {
    color: #fff;
    text-decoration-color: rgba(255, 255, 255, .6);
  }

  .lp-rich-content.on-dark a:hover {
    color: var(--lp-highlight);
  }

  .lp-rich-content.on-dark blockquote {
    background: rgba(255, 255, 255, .08);
    border-left-color: rgba(255, 255, 255, .5);
    color: rgba(255, 255, 255, .8);
  }

  /* ================= KOMPONEN BERSAMA: KOTAK INFO ================= */
  {{-- dipakai di berbagai partial (model3d, article, dst) via class="lp-info-box" --}} .lp-info-box {
    position: relative;
    background: linear-gradient(135deg, var(--lp-highlight-soft) 0%, transparent 130%);
    border: 1px solid color-mix(in srgb, var(--lp-highlight) 40%, transparent);
    border-radius: 16px;
    padding: 16px 18px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    font-size: .88rem;
    color: var(--lp-ink);
    line-height: 1.6;
    box-shadow: 0 10px 26px rgba(0, 0, 0, .06);
  }

  .lp-info-box i {
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    border-radius: 11px;
    background: var(--lp-highlight);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 4px 12px color-mix(in srgb, var(--lp-highlight) 45%, transparent);
  }

  /* Varian "info" (biru/aksen) — dipakai untuk info netral, beda dari peringatan (amber) */
  .lp-info-box.is-accent {
    background: linear-gradient(135deg, var(--lp-accent-soft) 0%, transparent 130%);
    border-color: color-mix(in srgb, var(--lp-accent) 40%, transparent);
  }

  .lp-info-box.is-accent i {
    background: var(--lp-accent);
    box-shadow: 0 4px 12px color-mix(in srgb, var(--lp-accent) 45%, transparent);
  }
</style>
