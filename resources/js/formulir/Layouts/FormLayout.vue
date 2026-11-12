<!-- resources/js/Layouts/FormLayout.vue -->

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { IconSchool, IconLogin } from "@tabler/icons-vue";

import CountdownBadge from "@/Components/CountdownBadge.vue";
import ThemeToggle from "@/Components/ThemeToggle.vue";

const props = defineProps({
    schoolName: {
        type: String,
        default: null,
    },

    endAt: {
        type: String,
        default: null,
    },

    // Progress pengisian
    // { answered: Number, total: Number }
    progress: {
        type: Object,
        default: null,
    },
});

const page = usePage();

const user = computed(() => page.props.auth?.user);

const isScrolled = ref(false);

/*
|--------------------------------------------------------------------------
| Progress
|--------------------------------------------------------------------------
*/

const progressPercent = computed(() => {
    if (!props.progress || !props.progress.total) {
        return 0;
    }

    return Math.min(
        100,
        Math.round((props.progress.answered / props.progress.total) * 100),
    );
});

/*
|--------------------------------------------------------------------------
| Scroll
|--------------------------------------------------------------------------
*/

const handleScroll = () => {
    isScrolled.value = window.scrollY > 10;
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    // Tema awal sudah dipasang skrip inisialisasi di app.blade.php
    // (kelas "dark" di <html>); pengalihnya komponen ThemeToggle.

    window.addEventListener("scroll", handleScroll, {
        passive: true,
    });

    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener("scroll", handleScroll);
});
</script>

<template>
    <div
        class="island-decor min-h-screen bg-canvas font-sans text-slate-900 antialiased transition-colors duration-300 dark:bg-[#09090b] dark:text-white"
    >
        <!-- ========================================================= -->
        <!-- TOP NAVIGATION -->
        <!-- ========================================================= -->

        <header
            class="sticky top-0 z-50 transition-all duration-300"
            :class="
                isScrolled
                    ? 'border-b border-slate-200 bg-white/85 shadow-[0_8px_30px_rgba(15,23,42,0.10)] backdrop-blur-2xl dark:border-zinc-800/70 dark:bg-zinc-950/80 dark:shadow-black/20'
                    : 'border-b border-transparent bg-transparent'
            "
        >
            <!-- Navbar -->
            <div
                class="mx-auto flex h-[68px] max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <!-- ================================================= -->
                <!-- BRAND / SCHOOL -->
                <!-- ================================================= -->

                <div class="flex min-w-0 items-center">
                    <div class="flex items-center gap-3">
                        <!-- Logo -->
                        <div
                            class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-[13px] bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-600 text-white shadow-[0_8px_25px_rgba(79,70,229,0.28)]"
                        >
                            <div class="absolute inset-0 bg-white/10"></div>

                            <IconSchool
                                :size="20"
                                stroke="1.8"
                                class="relative"
                            />
                        </div>

                        <!-- School -->
                        <div class="min-w-0 hidden sm:block">
                            <div
                                class="max-w-[240px] truncate text-[14px] font-bold tracking-[-0.02em] text-slate-900 dark:text-white sm:max-w-[360px]"
                            >
                                {{ schoolName ?? "Formulir K3S" }}
                            </div>

                            <div
                                class="text-[10px] font-medium uppercase tracking-[0.16em] text-slate-400 dark:text-zinc-500"
                            >
                                Form Pengisian
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================================================= -->
                <!-- RIGHT ACTIONS -->
                <!-- ================================================= -->

                <div class="flex shrink-0 items-center gap-1.5">
                    <!-- Countdown -->
                    <CountdownBadge v-if="endAt" :end-at="endAt" />

                    <!-- Theme -->
                    <ThemeToggle />

                    <!-- Divider -->
                    <div
                        class="mx-1 hidden h-6 w-px bg-slate-200 dark:bg-zinc-800 sm:block"
                    ></div>

                    <!-- User sudah login: identitas + navigasi pulau -->
                    <template v-if="user">
                        <div class="hidden items-center gap-2 sm:flex">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-xs font-bold text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                            >
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <span
                                class="max-w-[120px] truncate text-xs font-semibold text-slate-700 dark:text-zinc-300"
                            >
                                {{ user.name }}
                            </span>
                        </div>

                        <Link
                            :href="route('formulir.index')"
                            class="flex h-9 items-center rounded-xl px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
                        >
                            Daftar Form
                        </Link>

                        <a
                            href="/"
                            class="flex h-9 items-center rounded-xl px-3 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-800 dark:text-indigo-400 dark:hover:bg-indigo-500/10"
                        >
                            &larr; MySchool
                        </a>
                    </template>

                    <!-- Belum login: tampilkan tombol masuk -->
                    <Link
                        v-else
                        :href="route('login')"
                        class="flex h-9 items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        <IconLogin :size="16" stroke="1.9" />
                        <span class="hidden sm:block">Masuk</span>
                    </Link>
                </div>
            </div>

            <!-- ================================================= -->
            <!-- PROGRESS BAR -->
            <!-- ================================================= -->

            <div class="h-[3px] w-full bg-slate-200/70 dark:bg-zinc-800/70">
                <div
                    v-if="progress"
                    class="h-full bg-gradient-to-r from-indigo-500 via-indigo-600 to-violet-600 transition-all duration-500 ease-out"
                    :style="{
                        width: `${progressPercent}%`,
                    }"
                ></div>
            </div>
        </header>

        <!-- ========================================================= -->
        <!-- BANNER -->
        <!-- ========================================================= -->

        <slot name="banner"></slot>

        <!-- ========================================================= -->
        <!-- PAGE CONTENT -->
        <!-- ========================================================= -->

        <main class="mx-auto max-w-2xl px-4 py-3 sm:px-6 sm:py-10">
            <slot></slot>
        </main>

        <!-- ========================================================= -->
        <!-- FOOTER -->
        <!-- ========================================================= -->

        <footer
            class="border-t border-slate-200/80 py-6 text-center text-sm text-slate-500 dark:border-zinc-800/60 dark:text-zinc-500"
        >
            Formulir K3S — MySchool
        </footer>
    </div>
</template>
