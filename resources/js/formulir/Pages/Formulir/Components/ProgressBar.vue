<!-- resources/js/Pages/Form/Components/ProgressBar.vue -->
<script setup>
const props = defineProps({
    answered: Number,
    total: Number,
});
</script>

<template>
    <div class="mb-8">
        <div
            class="mb-1.5 flex items-center justify-between text-xs text-muted"
        >
            <span>{{ answered }} dari {{ total }} pertanyaan terisi</span>
            <span>{{ Math.round((answered / total) * 100) || 0 }}%</span>
        </div>
        <div class="h-1.5 overflow-hidden rounded-full bg-sand-dark/40">
            <div
                class="h-full rounded-full bg-brand transition-all duration-300 ease-out"
                :style="{ width: `${(answered / total) * 100 || 0}%` }"
            ></div>
        </div>
    </div>
</template>
