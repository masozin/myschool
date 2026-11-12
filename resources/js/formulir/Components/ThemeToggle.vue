<!-- Tombol pengalih mode terang/gelap pulau Formulir.
     Status awal dibaca dari kelas "dark" di <html> yang dipasang skrip
     inisialisasi di app.blade.php; pilihan disimpan di localStorage
     dengan kunci "theme" ("dark"/"light") — kunci yang sama dipakai
     seluruh pulau, jadi pilihan berlaku di semua halaman Formulir. -->
<script setup>
import { ref, onMounted } from "vue";
import { IconMoon, IconSun } from "@tabler/icons-vue";

const isDark = ref(false);

const sinkronDariDokumen = () => {
    isDark.value = document.documentElement.classList.contains("dark");
};

const gantiTema = () => {
    const gelap = !document.documentElement.classList.contains("dark");

    document.documentElement.classList.toggle("dark", gelap);

    try {
        localStorage.setItem("theme", gelap ? "dark" : "light");
    } catch (e) {
        // Penyimpanan lokal tidak tersedia: tema hanya berlaku sesi ini.
    }

    isDark.value = gelap;
};

onMounted(sinkronDariDokumen);
</script>

<template>
    <button
        type="button"
        @click="gantiTema"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-500 shadow-sm transition hover:border-indigo-300 hover:text-indigo-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
        :title="isDark ? 'Mode terang' : 'Mode gelap'"
        :aria-label="isDark ? 'Mode terang' : 'Mode gelap'"
    >
        <IconSun v-if="isDark" :size="18" stroke="1.7" />
        <IconMoon v-else :size="18" stroke="1.7" />
    </button>
</template>
