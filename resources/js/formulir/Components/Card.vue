<!-- resources/js/Components/Card.vue -->
<script setup>
defineProps({ hover: { type: Boolean, default: true } });
</script>

<template>
    <div
        class="rounded-2xl border border-slate-200 bg-surface p-6 shadow-card ring-1 ring-slate-900/5 transition-all duration-200 dark:border-zinc-800 dark:bg-surface-dark"
        :class="
            hover
                ? 'hover:-translate-y-0.5 hover:shadow-lift'
                : ''
        "
    >
        <slot />
    </div>
</template>
