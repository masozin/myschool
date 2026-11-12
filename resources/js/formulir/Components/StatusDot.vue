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
        class="inline-flex items-center gap-1.5 text-sm text-ink/80 dark:text-ink-dark/80"
    >
        <span
            class="h-1.5 w-1.5 rounded-full"
            :class="config[status]?.color"
        ></span>
        {{ config[status]?.label ?? status }}
    </span>
</template>
