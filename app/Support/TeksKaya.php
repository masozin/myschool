<?php

namespace App\Support;

use DOMDocument;
use DOMNode;

/**
 * Teks kaya (HTML terbatas) untuk isi berita, sambutan, visi & misi
 * Web Sekolah — ditulis lewat editor kaya di halaman kelola, tampil
 * sebagai HTML di web publik.
 *
 * Dua tugas:
 *  1. bersihkan() — sanitasi allowlist: hanya tag format dasar yang
 *     lolos (tebal/miring/garis bawah/daftar/judul kecil/kutipan/
 *     tautan); semua atribut dibuang kecuali href tautan yang
 *     skemanya aman; tag berbahaya (script/iframe/img/form/dll)
 *     dibuang beserta isinya, tag lain yang tak dikenal dibuka
 *     bungkusnya (isinya dipertahankan).
 *  2. tampil() — render aman untuk Blade: teks LAMA yang masih
 *     polos (tanpa tag) ditampilkan dengan nl2br(escaped) persis
 *     seperti sebelumnya; teks ber-HTML dibersihkan dulu.
 */
class TeksKaya
{
  /** Tag yang diizinkan pada hasil akhir. */
  private const TAG_BOLEH = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h3', 'h4', 'blockquote', 'a', 'span'];

  /** Tag yang dibuang BESERTA isinya (bukan dibuka bungkusnya). */
  private const TAG_BUANG = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'img', 'video', 'audio', 'link', 'meta', 'title', 'head'];

  public static function bersihkan(?string $html): ?string
  {
    if ($html === null) {
      return null;
    }
    $html = trim($html);
    if ($html === '') {
      return '';
    }
    // Teks polos tanpa tag sama sekali: simpan apa adanya — jangan
    // sampai DOM meng-escape '&' menjadi '&amp;' di basis data.
    if (strip_tags($html) === $html) {
      return $html;
    }
    if (!class_exists(DOMDocument::class)) {
      // Cadangan kasar bila ekstensi dom tidak ada: buang tag tak
      // dikenal lalu buang SEMUA atribut dari tag yang lolos.
      $kasar = strip_tags($html, '<' . implode('><', self::TAG_BOLEH) . '>');
      return preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $kasar);
    }

    $dom = new DOMDocument();
    $pesanLama = libxml_use_internal_errors(true);
    $dom->loadHTML(
      '<?xml encoding="utf-8" ?><div data-akar="1">' . $html . '</div>',
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($pesanLama);

    $akar = null;
    foreach ($dom->getElementsByTagName('div') as $div) {
      if ($div->getAttribute('data-akar') === '1') {
        $akar = $div;
        break;
      }
    }
    if (!$akar) {
      return strip_tags($html);
    }

    self::sikat($akar);

    $hasil = '';
    foreach (iterator_to_array($akar->childNodes) as $anak) {
      $hasil .= $dom->saveHTML($anak);
    }
    return trim($hasil);
  }

  /** Render aman untuk web publik ({!! !!} di Blade). */
  public static function tampil(?string $teks): string
  {
    if ($teks === null || trim($teks) === '') {
      return '';
    }
    if (strip_tags($teks) === $teks) {
      return nl2br(e(trim($teks)));
    }
    return self::bersihkan($teks) ?? '';
  }

  /** Sikat pohon DOM di bawah $node sesuai allowlist. */
  private static function sikat(DOMNode $node): void
  {
    foreach (iterator_to_array($node->childNodes) as $anak) {
      if ($anak->nodeType !== XML_ELEMENT_NODE) {
        continue;
      }
      $tag = strtolower($anak->nodeName);

      if (in_array($tag, self::TAG_BUANG, true)) {
        $node->removeChild($anak);
        continue;
      }
      if (!in_array($tag, self::TAG_BOLEH, true)) {
        // Buka bungkus: sikat isinya, naikkan ke level ini, lalu
        // buang elemen pembungkusnya.
        self::sikat($anak);
        while ($anak->firstChild) {
          $node->insertBefore($anak->firstChild, $anak);
        }
        $node->removeChild($anak);
        continue;
      }

      // Tag boleh: buang semua atribut kecuali href aman pada <a>.
      $href = $tag === 'a' ? $anak->getAttribute('href') : null;
      foreach (iterator_to_array($anak->attributes ?? []) as $atribut) {
        $anak->removeAttribute($atribut->nodeName);
      }
      if ($tag === 'a' && $href && self::hrefAman($href)) {
        $anak->setAttribute('href', $href);
      }
      self::sikat($anak);
    }
  }

  private static function hrefAman(string $href): bool
  {
    $href = trim($href);
    return (bool) preg_match('~^(https?://|mailto:|tel:|/|\#)~i', $href);
  }
}
