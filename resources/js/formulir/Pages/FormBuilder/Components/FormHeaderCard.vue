<!-- resources/js/Pages/FormBuilder/Components/FormHeaderCard.vue -->
<script setup>
import StatusDot from "@/Components/StatusDot.vue";
import { IconPencil, IconListNumbers, IconCalendar } from "@tabler/icons-vue";

const props = defineProps({
    form: Object,
    fieldCount: Number,
});
defineEmits(["edit"]);

function formatDate(d) {
    if (!d) return null;
    return new Date(d).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
}
</script>

<template>
    <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div v-if="form.banner_image" class="relative h-32 w-full sm:h-40">
            <img
                :src="`/storage/${form.banner_image}`"
                class="h-full w-full object-cover"
            />
            <div
                class="absolute inset-0 bg-gradient-to-t from-slate-950/50 to-transparent"
            ></div>
        </div>
        <div v-else class="relative h-16 w-full bg-slate-50 dark:bg-zinc-950">
            <div
                class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-indigo-500/10 blur-2xl"
            ></div>
        </div>

        <div
            class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="min-w-0">
                <h1
                    class="truncate text-xl font-bold tracking-[-0.01em] text-slate-900 dark:text-white sm:text-2xl"
                >
                    {{ form.title }}
                </h1>
                <div
                    class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-slate-500 dark:text-zinc-400"
                >
                    <StatusDot :status="form.status" />
                    <span
                        v-if="form.start_at || form.end_at"
                        class="flex items-center gap-1"
                    >
                        <IconCalendar :size="15" stroke="1.75" />
                        {{ formatDate(form.start_at) ?? "Kapan saja" }} —
                        {{ formatDate(form.end_at) ?? "Tanpa batas" }}
                    </span>
                    <span class="flex items-center gap-1">
                        <IconListNumbers :size="15" stroke="1.75" />
                        {{ fieldCount }} pertanyaan
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <button
                    @click="$emit('edit')"
                    class="flex shrink-0 items-center gap-2 self-start rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white sm:self-auto"
                >
                    <IconPencil :size="16" stroke="1.75" /> Edit Info Form
                </button>
            </div>
        </div>
    </div>
</template>
