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
            class="mb-1.5 block text-sm font-medium text-ink dark:text-ink-dark"
        >
            {{ label }}
        </label>
        <input
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            @input="$emit('update:modelValue', $event.target.value)"
            class="text-sm w-full rounded-md border-border dark:border-border-dark bg-surface dark:bg-surface-dark text-ink dark:text-ink-dark placeholder:text-muted/60 focus:border-brand focus:ring-brand/20 transition-colors duration-150"
            :class="{
                'border-red-400 focus:border-red-400 focus:ring-red-400/20':
                    error,
            }"
        />
        <p v-if="error" class="mt-1.5 text-sm text-red-500 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
