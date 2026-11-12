<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>Formulir — MySchool</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tema (terang/gelap): terapkan sebelum render agar tidak berkedip (FOUC).
         Kunci penyimpanan "theme" sama dengan yang dipakai komponen ThemeToggle. -->
    <script>
        try {
            var temaFormulir = localStorage.getItem('theme');
            if (temaFormulir === 'dark' || (!temaFormulir && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {
            // Penyimpanan lokal tidak tersedia: biarkan mode terang.
        }
    </script>

    <!-- Scripts -->
    @routes
    @vite(['resources/js/formulir/app.js', 'resources/css/formulir.css'])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
