<!-- resources/js/Pages/FormBuilder/Components/TypeAndSectionPicker.vue -->
<script setup>
import {
    IconLetterCase,
    IconAlignLeft,
    IconHash,
    IconSelector,
    IconCircleDot,
    IconSquareCheck,
    IconCalendar,
    IconMail,
    IconUpload,
    IconHeading,
    IconAlignJustified,
    IconLayoutRows,
} from "@tabler/icons-vue";

defineEmits(["pick-field", "pick-section"]);

const fieldTypes = [
    { type: "text", label: "Teks Singkat", icon: IconLetterCase },
    { type: "textarea", label: "Teks Panjang", icon: IconAlignLeft },
    { type: "number", label: "Angka", icon: IconHash },
    { type: "select", label: "Dropdown", icon: IconSelector },
    { type: "radio", label: "Pilihan Tunggal", icon: IconCircleDot },
    { type: "checkbox", label: "Pilihan Ganda", icon: IconSquareCheck },
    { type: "date", label: "Tanggal", icon: IconCalendar },
    { type: "email", label: "Email", icon: IconMail },
    { type: "file", label: "Unggah File", icon: IconUpload },
    { type: "heading", label: "Judul Bagian", icon: IconHeading },
    { type: "paragraph", label: "Teks Penjelasan", icon: IconAlignJustified },
];
</script>

<template>
    <div class="space-y-4">
        <button
            @click="$emit('pick-section')"
            class="flex w-full items-center gap-3 rounded-xl border border-dashed border-indigo-400 bg-indigo-50/70 p-3 text-left shadow-sm transition-all hover:border-indigo-500 hover:bg-indigo-50 dark:border-indigo-500/30 dark:bg-indigo-500/5 dark:hover:border-indigo-400/60 dark:hover:bg-indigo-500/10"
        >
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400"
            >
                <IconLayoutRows :size="18" stroke="1.75" />
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                    Tambah Bagian
                </p>
                <p class="text-xs text-slate-500 dark:text-zinc-400">
                    Kelompokkan pertanyaan di bawah judul baru
                </p>
            </div>
        </button>

        <div class="flex items-center gap-3">
            <div class="h-px flex-1 bg-slate-300 dark:bg-zinc-800"></div>
            <span class="text-xs text-slate-400 dark:text-zinc-500">
                atau pilih tipe pertanyaan
            </span>
            <div class="h-px flex-1 bg-slate-300 dark:bg-zinc-800"></div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <button
                v-for="t in fieldTypes"
                :key="t.type"
                @click="$emit('pick-field', t.type)"
                class="flex flex-col items-center gap-2 rounded-xl border border-slate-300 bg-white p-3 text-center shadow-sm transition-all duration-150 hover:-translate-y-px hover:border-indigo-400 hover:bg-indigo-50/60 hover:shadow-lift dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-indigo-400/50 dark:hover:bg-indigo-500/5"
            >
                <component
                    :is="t.icon"
                    :size="20"
                    stroke="1.5"
                    class="text-indigo-600 dark:text-indigo-400"
                />
                <span
                    class="text-xs font-semibold text-slate-700 dark:text-zinc-200"
                >
                    {{ t.label }}
                </span>
            </button>
        </div>
    </div>
</template>
