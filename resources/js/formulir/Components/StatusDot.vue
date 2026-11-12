<!-- resources/js/Components/StatusDot.vue -->
<script setup>
defineProps({ status: String });

const config = {
    draft: { color: "bg-muted dark:bg-muted-dark", label: "Draft" },
    published: { color: "bg-brand", label: "Terbit" },
    closed: { color: "bg-red-500", label: "Ditutup" },
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-xs font-semibold text-slate-700 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
    >
        <span
            class="h-2 w-2 shrink-0 rounded-full"
            :class="[config[status]?.color, status === 'published' ? 'animate-pulse' : '']"
        ></span>
        {{ config[status]?.label ?? status }}
    </span>
</template>
