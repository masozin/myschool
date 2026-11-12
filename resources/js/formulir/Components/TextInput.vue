<!-- resources/js/Components/TextInput.vue -->
<script setup>
defineProps({
    modelValue: [String, Number],
    label: String,
    error: String,
    type: { type: String, default: "text" },
    placeholder: String,
});
defineEmits(["update:modelValue"]);
</script>

<template>
    <div>
        <label
            v-if="label"
            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-zinc-200"
        >
            {{ label }}
        </label>
        <input
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-xl border-slate-300 bg-surface px-3.5 py-2.5 text-sm text-ink shadow-sm transition-all duration-150 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-zinc-700 dark:bg-surface-dark dark:text-ink-dark"
            :class="{
                'border-red-400 focus:border-red-400 focus:ring-red-400/20':
                    error,
            }"
        />
        <p v-if="error" class="mt-1.5 text-sm font-medium text-rose-600 dark:text-rose-400">
            {{ error }}
        </p>
    </div>
</template>
