<!-- resources/js/Pages/FormBuilder/Components/SectionHeader.vue -->
<script setup>
import InlineEditableText from "@/Components/InlineEditableText.vue";
import InlineEditableTextarea from "@/Components/InlineEditableTextarea.vue";
import {
    IconChevronDown,
    IconChevronUp,
    IconTrashX,
    IconArrowUp,
    IconArrowDown,
    IconPlus,
    IconGripVertical,
} from "@tabler/icons-vue";

defineProps({
    section: Object,
    isFirst: Boolean,
    isLast: Boolean,
});
defineEmits(["remove", "move-up", "move-down", "open-picker"]);
</script>

<template>
    <div class="group/section relative flex items-start gap-2 pt-2">
        <div
            class="absolute -right-11 top-0 hidden flex-col gap-1 opacity-0 transition-opacity duration-150 group-hover/section:opacity-100 sm:flex"
        >
            <button
                :disabled="isFirst"
                @click="$emit('move-up')"
                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400 hover:border-indigo-500 hover:text-indigo-600 disabled:opacity-30 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-500 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
            >
                <IconArrowUp :size="15" />
            </button>
            <button
                :disabled="isLast"
                @click="$emit('move-down')"
                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400 hover:border-indigo-500 hover:text-indigo-600 disabled:opacity-30 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-500 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
            >
                <IconArrowDown :size="15" />
            </button>
            <button
                @click="$emit('open-picker')"
                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400 hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-500 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                title="Tambah pertanyaan atau bagian di bawah ini"
            >
                <IconPlus :size="15" />
            </button>
        </div>

        <!-- class "drag-handle" disamakan dengan FieldCard supaya satu draggable bisa pakai satu handle selector -->
        <IconGripVertical
            :size="16"
            class="drag-handle mt-1.5 shrink-0 cursor-grab text-slate-300 dark:text-zinc-700"
        />

        <div
            class="min-w-0 flex-1 border-b border-slate-200 pb-3 dark:border-zinc-800"
        >
            <div class="flex items-center justify-between gap-2">
                <InlineEditableText
                    v-model="section.title"
                    placeholder="Judul Bagian"
                    tag="h3"
                    text-class="text-lg font-bold text-slate-900 dark:text-white"
                />
                <div class="flex shrink-0 items-center gap-1">
                    <button
                        @click="section._collapsed = !section._collapsed"
                        class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                    >
                        <component
                            :is="
                                section._collapsed
                                    ? IconChevronDown
                                    : IconChevronUp
                            "
                            :size="16"
                        />
                    </button>
                    <button
                        @click="$emit('remove')"
                        class="rounded p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-500 dark:text-zinc-500 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                    >
                        <IconTrashX :size="16" />
                    </button>
                </div>
            </div>
            <InlineEditableTextarea
                v-model="section.description"
                placeholder="Tambah keterangan bagian..."
                text-class="text-sm text-slate-500 dark:text-zinc-400 mt-0.5"
            />
        </div>
    </div>
</template>
