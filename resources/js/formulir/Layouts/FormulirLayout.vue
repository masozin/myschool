<!-- Layout ramping pulau Formulir (pengganti AdminLayout form-k3s). -->
<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);
const isAdmin = computed(
    () => user.value?.roles?.includes("Admin") ?? false,
);
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashError = computed(() => page.props.flash?.error ?? null);
</script>

<template>
    <div class="island-decor min-h-screen bg-canvas text-slate-900">
        <!-- Top bar -->
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/85 shadow-[0_1px_12px_rgba(15,23,42,0.05)] backdrop-blur-xl">
            <div class="mx-auto flex h-14 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                <span class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-[10px] bg-brand-gradient text-[11px] font-extrabold tracking-wide text-white shadow-glow">FK</span>
                    <span class="text-base font-bold tracking-tight">
                        Formulir <span class="text-indigo-600">K3S</span>
                    </span>
                </span>

                <nav class="flex items-center gap-1 text-sm font-medium">
                    <Link
                        :href="route('formulir.index')"
                        class="rounded-xl px-3 py-1.5 text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"
                    >
                        Formulir
                    </Link>
                    <Link
                        v-if="isAdmin"
                        :href="route('formulir.kelola.index')"
                        class="rounded-xl px-3 py-1.5 text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"
                    >
                        Kelola Form
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-4">
                    <span
                        v-if="user"
                        class="hidden text-sm font-medium text-slate-600 sm:inline"
                    >
                        {{ user.name }}
                    </span>
                    <a
                        href="/"
                        class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-800"
                    >
                        &larr; Kembali ke MySchool
                    </a>
                </div>
            </div>

            <!-- Baris breadcrumb dari halaman (slot header, opsional) -->
            <div
                v-if="$slots.header"
                class="border-t border-slate-200/70"
            >
                <div class="mx-auto max-w-7xl px-4 py-2.5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            <div
                v-if="flashSuccess"
                class="mx-auto mb-5 max-w-7xl rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm"
            >
                {{ flashSuccess }}
            </div>
            <div
                v-if="flashError"
                class="mx-auto mb-5 max-w-7xl rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 shadow-sm"
            >
                {{ flashError }}
            </div>

            <slot />
        </main>
    </div>
</template>
