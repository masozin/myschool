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
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <!-- Top bar -->
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-14 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                <span class="text-base font-bold tracking-tight">
                    Formulir <span class="text-indigo-600">K3S</span>
                </span>

                <nav class="flex items-center gap-1 text-sm font-medium">
                    <Link
                        :href="route('formulir.index')"
                        class="rounded-lg px-3 py-1.5 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        Formulir
                    </Link>
                    <Link
                        v-if="isAdmin"
                        :href="route('formulir.kelola.index')"
                        class="rounded-lg px-3 py-1.5 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        Kelola Form
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-4">
                    <span
                        v-if="user"
                        class="hidden text-sm text-slate-500 sm:inline"
                    >
                        {{ user.name }}
                    </span>
                    <a
                        href="/"
                        class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800"
                    >
                        &larr; Kembali ke MySchool
                    </a>
                </div>
            </div>

            <!-- Baris breadcrumb dari halaman (slot header, opsional) -->
            <div
                v-if="$slots.header"
                class="border-t border-slate-100"
            >
                <div class="mx-auto max-w-7xl px-4 py-2.5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            <div
                v-if="flashSuccess"
                class="mx-auto mb-5 max-w-7xl rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
            >
                {{ flashSuccess }}
            </div>
            <div
                v-if="flashError"
                class="mx-auto mb-5 max-w-7xl rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600"
            >
                {{ flashError }}
            </div>

            <slot />
        </main>
    </div>
</template>
