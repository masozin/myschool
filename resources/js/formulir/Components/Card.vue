<!-- resources/js/Components/Card.vue -->
<script setup>
defineProps({ hover: { type: Boolean, default: true } });
</script>

<template>
    <div
        class="rounded-xl border border-border dark:border-border-dark bg-surface dark:bg-surface-dark p-6 transition-shadow duration-200"
        :class="
            hover
                ? 'shadow-premium dark:shadow-premium-dark hover:shadow-glow'
                : ''
        "
    >
        <slot />
    </div>
</template>
