<!-- resources/js/Pages/FormBuilder/Submissions/Show.vue -->
<script setup>
import { computed } from "vue";
import FormulirLayout from "@/Layouts/FormulirLayout.vue";
import { Link } from "@inertiajs/vue3";
import {
    IconArrowLeft,
    IconSchool,
    IconCalendar,
    IconFile,
    IconDownload,
    IconHash,
} from "@tabler/icons-vue";

const props = defineProps({
    form: Object,
    submission: Object,
});

const answerableItems = computed(() => {
    const loose = (props.form.fields ?? []).map((f) => ({
        ...f,
        sectionTitle: null,
    }));
    const sectioned = (props.form.sections ?? []).flatMap((s) =>
        (s.fields ?? []).map((f) => ({ ...f, sectionTitle: s.title })),
    );
    return [...loose, ...sectioned].sort((a, b) => a.sort_order - b.sort_order);
});

function formatDateTime(d) {
    if (!d) return "—";
    return new Date(d).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function formatTanggal(d) {
    if (!d) return null;
    const parsed = new Date(d);
    if (isNaN(parsed.getTime())) return String(d);
    return parsed.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
}

function answerFor(field) {
    if (field.type === "file") {
        return props.submission.files?.[field.name] ?? null;
    }
    const val = props.submission.data?.[field.name];
    if (val === null || val === undefined || val === "") return null;
    if (field.type === "date") return formatTanggal(val);
    if (Array.isArray(val)) return val.length ? val.join(", ") : null;
    return String(val);
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
                    :href="route('formulir.kelola.submissions.index', form.id)"
                    class="truncate font-medium text-slate-500 transition hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white"
                    :title="form.title"
                >
                    {{ form.title }}
                </Link>
                <span class="text-slate-300 dark:text-zinc-700">/</span>
                <span class="shrink-0 font-semibold text-slate-900 dark:text-white">
                    Detail Jawaban
                </span>
            </div>
        </template>

        <div class="mx-auto max-w-3xl">
            <Link
                :href="route('formulir.kelola.submissions.index', form.id)"
                class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white"
            >
                <IconArrowLeft :size="16" /> Kembali ke Daftar Jawaban
            </Link>

            <!-- Header pengisi -->
            <div
                class="mb-6 rounded-[24px] border border-slate-200 bg-white p-5 shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black/20 sm:p-6"
            >
                <div class="flex items-start gap-3.5">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-base font-bold text-white shadow-[0_8px_20px_rgba(79,70,229,0.25)]"
                    >
                        {{ (submission.pengisi.nama ?? "?").charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-display text-lg font-bold text-slate-900 dark:text-white">
                                {{ submission.pengisi.nama }}
                            </p>
                            <span
                                v-if="submission.pengisi.is_tamu"
                                class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"
                            >
                                TAMU
                            </span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-zinc-400">
                            <span v-if="submission.pengisi.sekolah" class="flex items-center gap-1">
                                <IconSchool :size="12" />
                                {{ submission.pengisi.sekolah }}
                            </span>
                            <span class="flex items-center gap-1">
                                <IconCalendar :size="12" />
                                {{ formatDateTime(submission.submitted_at) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-4 flex items-center gap-1.5 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:bg-zinc-800/60 dark:text-zinc-400"
                >
                    <IconHash :size="13" />
                    <span class="font-mono">{{ submission.submission_token }}</span>
                </div>
            </div>

            <!-- Jawaban -->
            <div class="space-y-3">
                <template v-for="(field, index) in answerableItems" :key="field.id">
                    <!-- Judul bagian, tampil sekali di awal grupnya -->
                    <div
                        v-if="
                            field.sectionTitle &&
                            (index === 0 ||
                                answerableItems[index - 1].sectionTitle !== field.sectionTitle)
                        "
                        class="pb-1 pt-4 first:pt-0"
                    >
                        <h3 class="font-display text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-zinc-400">
                            {{ field.sectionTitle }}
                        </h3>
                    </div>

                    <!-- Heading/paragraph sebagai konteks -->
                    <div v-if="field.type === 'heading'" class="pt-2">
                        <h4 class="font-display text-base font-bold text-slate-900 dark:text-white">
                            {{ field.label }}
                        </h4>
                    </div>
                    <p v-else-if="field.type === 'paragraph'" class="text-sm text-slate-500 dark:text-zinc-400">
                        {{ field.label }}
                    </p>

                    <!-- Jawaban field -->
                    <div
                        v-else
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-5"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-zinc-500">
                            {{ field.label }}
                        </p>

                        <a
                            v-if="field.type === 'file' && answerFor(field)"
                            :href="answerFor(field).url"
                            target="_blank"
                            rel="noopener"
                            class="mt-2 flex items-center gap-2 text-sm font-semibold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            <IconFile :size="16" stroke="1.75" />
                            <span class="truncate">{{ answerFor(field).original_name }}</span>
                            <span class="shrink-0 text-xs font-medium text-slate-400 dark:text-zinc-500">
                                ({{ answerFor(field).size }})
                            </span>
                            <IconDownload :size="14" stroke="1.75" class="shrink-0" />
                        </a>
                        <p
                            v-else-if="field.type === 'file'"
                            class="mt-2 text-sm italic text-slate-400 dark:text-zinc-500"
                        >
                            Tidak dijawab
                        </p>

                        <p
                            v-else
                            class="mt-2 whitespace-pre-line text-sm text-slate-800 dark:text-zinc-100"
                            :class="{ 'italic text-slate-400 dark:text-zinc-500': !answerFor(field) }"
                        >
                            {{ answerFor(field) ?? "Tidak dijawab" }}
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </FormulirLayout>
</template>
