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
            class="mb-2 flex items-center justify-between text-xs font-semibold text-slate-600 dark:text-zinc-300"
        >
            <span>{{ answered }} dari {{ total }} pertanyaan terisi</span>
            <span>{{ Math.round((answered / total) * 100) || 0 }}%</span>
        </div>
        <div class="h-2.5 overflow-hidden rounded-full bg-slate-200 dark:bg-zinc-800">
            <div
                class="h-full rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 transition-all duration-300 ease-out"
                :style="{ width: `${(answered / total) * 100 || 0}%` }"
            ></div>
        </div>
    </div>
</template>
