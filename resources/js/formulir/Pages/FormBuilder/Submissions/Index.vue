<!-- resources/js/Pages/FormBuilder/Submissions/Index.vue -->
<script setup>
import { ref, computed, watch } from "vue";
import FormulirLayout from "@/Layouts/FormulirLayout.vue";
import StatusDot from "@/Components/StatusDot.vue";
import { Link, router } from "@inertiajs/vue3";
import {
    IconSearch,
    IconUsers,
    IconSchool,
    IconChevronRight,
    IconInbox,
    IconArrowLeft,
    IconFileSpreadsheet,
    IconFileZip,
    IconClipboardList,
} from "@tabler/icons-vue";

const props = defineProps({
    form: Object,
    submissions: Object,
    filters: Object,
    ringkasan: Object,
    monitoring: { type: Array, default: () => [] },
});

const search = ref(props.filters.search ?? "");
const activeTab = ref("jawaban");
const monitorFilter = ref("semua");

function debounce(fn, wait = 400) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
    };
}

watch(
    search,
    debounce((val) => {
        router.get(
            route("formulir.kelola.submissions.index", props.form.id),
            { search: val },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }),
);

const filteredMonitoring = computed(() => {
    if (monitorFilter.value === "sudah") {
        return props.monitoring.filter((s) => s.sudah);
    }
    if (monitorFilter.value === "belum") {
        return props.monitoring.filter((s) => !s.sudah);
    }
    return props.monitoring;
});

const belumCount = computed(
    () => props.ringkasan.sekolah_total - props.ringkasan.sekolah_sudah,
);

function formatDateTime(d) {
    if (!d) return "—";
    return new Date(d).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}
</script>

<template>
    <FormulirLayout>
        <template #header>
            <div class="flex min-w-0 items-center gap-2 text-sm">
                <Link
                    :href="route('formulir.kelola.index')"
                    class="inline-flex shrink-0 items-center gap-1.5 text-slate-500 transition hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white"
                >
                    <IconArrowLeft :size="16" />
                    <span class="hidden sm:inline">Form</span>
                </Link>
                <span class="text-slate-300 dark:text-zinc-700">/</span>
                <Link
                    :href="route('formulir.kelola.edit', form.id)"
                    class="truncate font-medium text-slate-500 transition hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white"
                    :title="form.title"
                >
                    {{ form.title }}
                </Link>
                <span class="text-slate-300 dark:text-zinc-700">/</span>
                <span class="shrink-0 font-semibold text-slate-900 dark:text-white">
                    Jawaban
                </span>
            </div>
        </template>

        <div class="mx-auto max-w-6xl">
            <!-- Judul + ekspor -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Daftar Jawaban
                        </h1>
                        <StatusDot :status="form.status" />
                    </div>
                    <p class="mt-1 truncate text-sm text-slate-500 dark:text-zinc-400" :title="form.title">
                        {{ form.title }}
                    </p>
                </div>

                <div v-if="ringkasan.total_jawaban > 0" class="flex shrink-0 flex-wrap gap-2">
                    <a
                        :href="route('formulir.kelola.submissions.export', form.id)"
                        class="inline-flex min-h-[40px] items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-px hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500/30 active:scale-[.98]"
                    >
                        <IconFileSpreadsheet :size="16" stroke="1.75" />
                        Ekspor Excel
                    </a>
                    <a
                        :href="route('formulir.kelola.submissions.exportFiles', form.id)"
                        class="inline-flex min-h-[40px] items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:-translate-y-px hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-800 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500/30 active:scale-[.98] dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-white"
                    >
                        <IconFileZip :size="16" stroke="1.75" />
                        Ekspor Berkas (ZIP)
                    </a>
                </div>
            </div>

            <!-- Ringkasan -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black/20">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                            <IconUsers :size="19" stroke="1.75" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">
                                {{ ringkasan.total_jawaban }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-zinc-400">Total jawaban masuk</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black/20">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <IconSchool :size="19" stroke="1.75" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">
                                {{ ringkasan.sekolah_sudah }}
                                <span class="text-base font-semibold text-slate-400 dark:text-zinc-500">/ {{ ringkasan.sekolah_total }}</span>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-zinc-400">Sekolah sudah mengisi</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black/20">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                            <IconSchool :size="19" stroke="1.75" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">
                                {{ belumCount }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-zinc-400">Sekolah belum mengisi</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-5 inline-flex items-center rounded-xl border border-slate-300 bg-white p-1 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <button
                    type="button"
                    @click="activeTab = 'jawaban'"
                    class="flex h-9 items-center gap-2 rounded-lg px-4 text-sm font-semibold transition-all"
                    :class="activeTab === 'jawaban'
                        ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900'
                        : 'text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-white'"
                >
                    <IconClipboardList :size="16" stroke="1.8" />
                    Daftar Jawaban
                </button>
                <button
                    type="button"
                    @click="activeTab = 'sekolah'"
                    class="flex h-9 items-center gap-2 rounded-lg px-4 text-sm font-semibold transition-all"
                    :class="activeTab === 'sekolah'
                        ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900'
                        : 'text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-white'"
                >
                    <IconSchool :size="16" stroke="1.8" />
                    Per Sekolah
                </button>
            </div>

            <!-- TAB: Daftar jawaban -->
            <div v-if="activeTab === 'jawaban'">
                <div class="relative mb-4">
                    <IconSearch
                        :size="17"
                        stroke="1.75"
                        class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-zinc-500"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama pengisi, sekolah, atau isi jawaban..."
                        class="h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/15 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-600 dark:focus:border-indigo-500"
                    />
                </div>

                <div
                    v-if="submissions.data.length === 0"
                    class="flex flex-col items-center rounded-[24px] border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-card dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-zinc-800 dark:text-zinc-500">
                        <IconInbox :size="26" stroke="1.5" />
                    </div>
                    <p class="mt-4 text-sm text-slate-500 dark:text-zinc-400">
                        {{
                            search
                                ? "Tidak ada jawaban yang cocok dengan pencarian."
                                : "Belum ada jawaban yang masuk untuk form ini."
                        }}
                    </p>
                </div>

                <div v-else class="space-y-3">
                    <Link
                        v-for="submission in submissions.data"
                        :key="submission.id"
                        :href="route('formulir.kelola.submissions.show', [form.id, submission.id])"
                        class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card ring-1 ring-slate-900/5 transition-all duration-200 hover:-translate-y-[1px] hover:border-indigo-300 hover:shadow-lift dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-indigo-500/30 sm:p-5"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    {{ submission.pengisi.nama }}
                                </p>
                                <span
                                    v-if="submission.pengisi.is_tamu"
                                    class="rounded-full bg-slate-100 px-2 py-0.5 font-mono text-[10px] font-bold text-slate-500 dark:bg-zinc-800 dark:text-zinc-400"
                                >
                                    #{{ submission.pengisi.token_pendek }}
                                </span>
                                <span
                                    v-if="submission.pengisi.sekolah"
                                    class="flex items-center gap-1 text-xs text-slate-500 dark:text-zinc-400"
                                >
                                    <IconSchool :size="12" />
                                    {{ submission.pengisi.sekolah }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-zinc-400">
                                {{ formatDateTime(submission.submitted_at) }}
                            </p>
                            <div
                                v-if="submission.preview.length"
                                class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1"
                            >
                                <div v-for="(p, i) in submission.preview" :key="i" class="text-xs">
                                    <span class="text-slate-400 dark:text-zinc-500">{{ p.label }}:</span>
                                    <span class="ml-1 font-medium text-slate-700 dark:text-zinc-200">{{ p.value }}</span>
                                </div>
                            </div>
                        </div>
                        <IconChevronRight
                            :size="18"
                            class="shrink-0 text-slate-400 transition-transform duration-150 group-hover:translate-x-0.5 dark:text-zinc-500"
                        />
                    </Link>
                </div>

                <div
                    v-if="submissions.links.length > 3"
                    class="mt-6 flex flex-wrap items-center justify-center gap-1"
                >
                    <template v-for="(link, i) in submissions.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            preserve-scroll
                            class="rounded-lg px-3 py-1.5 text-sm transition"
                            :class="link.active
                                ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900'
                                : 'text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white'"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="rounded-lg px-3 py-1.5 text-sm text-slate-300 dark:text-zinc-600"
                        />
                    </template>
                </div>
            </div>

            <!-- TAB: Per sekolah -->
            <div v-else>
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <button
                        v-for="f in [
                            { key: 'semua', label: 'Semua' },
                            { key: 'sudah', label: 'Sudah Mengisi' },
                            { key: 'belum', label: 'Belum Mengisi' },
                        ]"
                        :key="f.key"
                        type="button"
                        @click="monitorFilter = f.key"
                        class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition"
                        :class="monitorFilter === f.key
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'border border-slate-300 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:text-white'"
                    >
                        {{ f.label }}
                    </button>
                    <p class="ml-auto text-xs font-medium text-slate-400 dark:text-zinc-500">
                        Jawaban tamu (tanpa login) tidak terhitung per sekolah.
                    </p>
                </div>

                <div
                    v-if="filteredMonitoring.length === 0"
                    class="flex flex-col items-center rounded-[24px] border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-card dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-zinc-800 dark:text-zinc-500">
                        <IconSchool :size="26" stroke="1.5" />
                    </div>
                    <p class="mt-4 text-sm text-slate-500 dark:text-zinc-400">
                        Tidak ada sekolah pada kelompok ini.
                    </p>
                </div>

                <div v-else class="space-y-2.5">
                    <div
                        v-for="sekolah in filteredMonitoring"
                        :key="sekolah.id"
                        class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 sm:px-5"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                :class="sekolah.sudah
                                    ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'
                                    : 'bg-slate-100 text-slate-400 dark:bg-zinc-800 dark:text-zinc-500'"
                            >
                                <IconSchool :size="17" stroke="1.75" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-zinc-100">
                                    {{ sekolah.nama }}
                                </p>
                                <p class="text-xs text-slate-400 dark:text-zinc-500">
                                    NPSN {{ sekolah.npsn }}
                                    <template v-if="sekolah.sudah">
                                        · terakhir {{ formatDateTime(sekolah.terakhir) }}
                                    </template>
                                </p>
                            </div>
                        </div>
                        <span
                            v-if="sekolah.sudah"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
                        >
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Sudah · {{ sekolah.jumlah }} jawaban
                        </span>
                        <span
                            v-else
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400"
                        >
                            <span class="h-2 w-2 rounded-full bg-slate-400 dark:bg-zinc-500"></span>
                            Belum
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </FormulirLayout>
</template>
