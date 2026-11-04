<div class="anim-up">
  @if ($slide->title)
    <h3 class="fw-bold mb-3">{{ $slide->title }}</h3>
  @endif

  <iframe id="html-slide-frame-{{ $slide->id }}" style="width:100%; border:0; display:block;"
    sandbox="allow-scripts allow-same-origin" srcdoc="{{ $slide->content_text }}">
  </iframe>
</div>

<script>
  (function() {
    const frame = document.getElementById('html-slide-frame-{{ $slide->id }}');
    frame.addEventListener('load', function() {
      try {
        const h = frame.contentWindow.document.documentElement.scrollHeight;
        frame.style.height = (h + 24) + 'px';
      } catch (e) {
        frame.style.height = '400px'; // fallback kalau gagal baca tinggi konten
      }
    });
  })();
</script>
