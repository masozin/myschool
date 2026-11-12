<!-- resources/js/Pages/Form/Review.vue -->
<script setup>
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import { formatDate, parseISO } from "@/utils/dateFormat";

import SubmissionSuccessModal from "./Components/SubmissionSuccessModal.vue";
import FormLayout from "@/Layouts/FormLayout.vue";

import {
    IconPencil,
    IconArrowLeft,
    IconPaperclip,
    IconLock,
} from "@tabler/icons-vue";

const props = defineProps({
    form: Object,
    submission: Object,
    canEdit: Boolean,
    editUrl: String,
    isGuest: Boolean,
});

const page = usePage();
const successPayload = computed(
    () => page.props.flash?.success_submission ?? null,
);

const isStatic = (field) => ["heading", "paragraph"].includes(field.type);

function displayValue(field) {
    const value = props.submission.data?.[field.name];

    if (Array.isArray(value)) {
        return value.length ? value.join(", ") : null;
    }
    if (value === null || value === undefined || String(value).trim() === "") {
        return null;
    }
    return String(value);
}

function fileFor(field) {
    return props.submission.files?.[field.name] ?? null;
}
</script>

<template>
    <FormLayout :end-at="form.end_at">
        <template #banner>
            <div
                v-if="form.banner_image"
                class="relative h-48 w-full overflow-hidden sm:h-64"
            >
                <img
                    :src="`/storage/${form.banner_image}`"
                    class="h-full w-full object-cover"
                    :alt="form.title"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-slate-950/10 to-transparent"
                ></div>
            </div>
        </template>

        <Link
            v-if="form.allow_multiple_submissions && !isGuest"
            :href="route('formulir.publik.show', form.slug)"
            class="mb-4 flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white"
        >
            <IconArrowLeft :size="16" />
            Kembali ke Riwayat Jawaban
        </Link>

        <!-- HEADER -->
        <div class="mb-6">
            <h1
                class="text-2xl font-bold tracking-[-0.01em] text-slate-900 dark:text-white sm:text-3xl"
            >
                {{ form.title }}
            </h1>
            <p
                v-if="form.description"
                class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-zinc-400"
            >
                {{ form.description }}
            </p>
        </div>

        <!-- INFO + TOMBOL EDIT -->
        <div
            class="mb-6 flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm dark:bg-emerald-500/10 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="text-sm text-emerald-800 dark:text-emerald-300">
                <p class="font-medium">Jawaban kamu sudah terkirim.</p>
                <p class="mt-0.5 text-xs opacity-80">
                    Token: {{ submission.submission_token }}
                    <span v-if="submission.submitted_at">
                        · {{ formatDate(parseISO(submission.submitted_at)) }}
                    </span>
                </p>
            </div>

            <Link
                v-if="canEdit"
                :href="editUrl"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            >
                <IconPencil :size="16" stroke="1.75" />
                Edit Jawaban
            </Link>

            <span
                v-else
                class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-zinc-400"
            >
                <IconLock :size="14" />
                Form sudah ditutup, jawaban tidak bisa diubah
            </span>
        </div>

        <div class="space-y-4">
            <!-- FIELD TANPA SECTION -->
            <template v-for="field in form.fields" :key="field.id">
                <div v-if="isStatic(field)" class="px-1">
                    <h3
                        v-if="field.type === 'heading'"
                        class="text-lg font-bold text-slate-900 dark:text-white"
                    >
                        {{ field.label }}
                    </h3>
                    <p
                        v-else
                        class="text-sm text-slate-500 dark:text-zinc-400"
                    >
                        {{ field.label }}
                    </p>
                </div>

                <div
                    v-else
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-5"
                >
                    <p
                        class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-zinc-400"
                    >
                        {{ field.label }}
                    </p>

                    <template v-if="field.type === 'file'">
                        <a
                            v-if="fileFor(field)"
                            :href="fileFor(field).url"
                            target="_blank"
                            class="mt-1 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            <IconPaperclip :size="16" />
                            {{ fileFor(field).original_name }}
                        </a>
                        <p v-else class="mt-1 font-medium text-slate-500">—</p>
                    </template>

                    <p
                        v-else
                        class="mt-1 whitespace-pre-line text-slate-900 dark:text-white"
                    >
                        {{ displayValue(field) ?? "—" }}
                    </p>
                </div>
            </template>

            <!-- SECTION -->
            <div
                v-for="(section, sectionIndex) in form.sections"
                :key="section.id"
                class="space-y-4"
            >
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-slate-300 pb-3 dark:border-zinc-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-brand-gradient text-sm font-bold text-white shadow-glow">{{ sectionIndex + 1 }}</span>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ section.title }}
                    </h2>
                    <p
                        v-if="section.description"
                        class="mt-1 w-full pl-[44px] text-sm text-slate-600 dark:text-zinc-400"
                    >
                        {{ section.description }}
                    </p>
                </div>

                <template v-for="field in section.fields" :key="field.id">
                    <div v-if="isStatic(field)" class="px-1">
                        <h3
                            v-if="field.type === 'heading'"
                            class="text-lg font-bold text-slate-900 dark:text-white"
                        >
                            {{ field.label }}
                        </h3>
                        <p
                            v-else
                            class="text-sm text-slate-500 dark:text-zinc-400"
                        >
                            {{ field.label }}
                        </p>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-5"
                    >
                        <p
                            class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-zinc-400"
                        >
                            {{ field.label }}
                        </p>

                        <template v-if="field.type === 'file'">
                            <a
                                v-if="fileFor(field)"
                                :href="fileFor(field).url"
                                target="_blank"
                                class="mt-1 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                <IconPaperclip :size="16" />
                                {{ fileFor(field).original_name }}
                            </a>
                            <p v-else class="mt-1 font-medium text-slate-500">—</p>
                        </template>

                        <p
                            v-else
                            class="mt-1 whitespace-pre-line text-slate-900 dark:text-white"
                        >
                            {{ displayValue(field) ?? "—" }}
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </FormLayout>

    <SubmissionSuccessModal :payload="successPayload" />
</template>