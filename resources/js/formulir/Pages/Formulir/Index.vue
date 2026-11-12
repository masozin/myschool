<script setup>
import { Head, Link } from "@inertiajs/vue3";
import {
    IconCircleCheck,
    IconCircleDashed,
    IconClockHour4,
    IconForms,
    IconSettings,
} from "@tabler/icons-vue";
import FormulirLayout from "@/Layouts/FormulirLayout.vue";
import { formatDate } from "@/utils/dateFormat";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
    sekolah: {
        type: Object,
        default: null,
    },
    activeForms: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            forms_active: 0,
            forms_submitted: 0,
            forms_pending: 0,
        }),
    },
});
</script>

<template>
    <Head title="Formulir K3S" />

    <FormulirLayout>
        <div class="mx-auto max-w-4xl">
            <header class="relative mb-6 overflow-hidden rounded-[24px] bg-brand-gradient px-6 py-6 text-white shadow-lift sm:px-7">
                <div class="pointer-events-none absolute -right-16 -top-24 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
                <p class="relative text-[11px] font-bold uppercase tracking-[0.16em] text-indigo-100">
                    Modul Formulir K3S
                </p>
                <h1 class="relative mt-1 text-2xl font-bold">
                    Form yang Bisa Diisi
                </h1>
                <p class="relative mt-1 text-sm text-indigo-100">
                    Halo, {{ props.user.name }}
                    <span v-if="props.sekolah">
                        — {{ props.sekolah.nama ?? "Sekolah" }} (NPSN
                        {{ props.sekolah.npsn }})
                    </span>
                </p>
            </header>

            <div
                v-if="props.isAdmin"
                class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4"
            >
                <p class="text-sm text-amber-800">
                    Kamu adalah Admin — daftar di bawah menampilkan semua form
                    yang sedang terbuka untuk semua sekolah dan role.
                </p>
                <Link
                    :href="route('formulir.kelola.index')"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-amber-700"
                >
                    <IconSettings :size="16" stroke="1.8" />
                    Kelola Formulir
                </Link>
            </div>

            <section
                class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-900/5 sm:p-6"
            >
                <div class="mb-4 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700">
                        {{ props.stats.forms_active }} form aktif
                    </span>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">
                        {{ props.stats.forms_submitted }} sudah diisi
                    </span>
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700">
                        {{ props.stats.forms_pending }} belum diisi
                    </span>
                </div>

                <div
                    v-if="props.activeForms.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center"
                >
                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200/70 text-slate-500"
                    >
                        <IconForms :size="24" stroke="1.6" />
                    </div>
                    <p class="mt-3 text-sm text-slate-500">
                        Tidak ada form aktif untuk kamu saat ini.
                    </p>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="form in props.activeForms"
                        :key="form.id"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 border-l-4 p-4 shadow-card ring-1 ring-slate-900/5 transition hover:-translate-y-px hover:shadow-lift"
                        :class="form.has_submitted ? 'border-l-emerald-500' : 'border-l-indigo-500'"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                :class="
                                    form.has_submitted
                                        ? 'bg-emerald-50 text-emerald-600'
                                        : 'bg-indigo-50 text-indigo-600'
                                "
                            >
                                <component
                                    :is="
                                        form.has_submitted
                                            ? IconCircleCheck
                                            : IconCircleDashed
                                    "
                                    :size="17"
                                    stroke="1.75"
                                />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">
                                    {{ form.title }}
                                </p>
                                <p class="flex flex-wrap items-center gap-x-2 text-xs text-slate-500">
                                    <span>{{ form.sekolah_nama ?? "Semua Sekolah" }}</span>
                                    <span
                                        v-if="form.end_at"
                                        class="inline-flex items-center gap-1"
                                    >
                                        <IconClockHour4 :size="12" />
                                        Batas: {{ formatDate(form.end_at) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm"
                                :class="
                                    form.has_submitted
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-indigo-50 text-indigo-700'
                                "
                            >
                                {{
                                    form.has_submitted
                                        ? form.allow_multiple_submissions
                                            ? "Sudah diisi · Bisa isi lagi"
                                            : "Sudah diisi"
                                        : "Belum diisi"
                                }}
                            </span>
                            <Link
                                :href="route('formulir.publik.show', form.slug)"
                                class="rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                            >
                                {{ form.has_submitted ? "Lihat/Edit Jawaban" : "Isi Form" }}
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </FormulirLayout>
</template>
