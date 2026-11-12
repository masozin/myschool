<!-- resources/js/Pages/Form/Components/inputs/CheckboxField.vue -->
<script setup>
const props = defineProps({ field: Object, modelValue: Array, error: String });
const emit = defineEmits(["update:modelValue", "blur"]);

function toggle(value) {
    const current = props.modelValue ?? [];
    const next = current.includes(value)
        ? current.filter((v) => v !== value)
        : [...current, value];
    emit("update:modelValue", next);
    emit("blur");
}
</script>

<template>
    <div class="space-y-2.5">
        <label
            v-for="opt in field.config?.options ?? []"
            :key="opt.value"
            class="flex cursor-pointer items-center gap-2.5"
        >
            <span
                class="inline-flex transition-transform duration-150 ease-out"
                :class="(modelValue ?? []).includes(opt.value) && 'scale-110'"
            >
                <input
                    type="checkbox"
                    :checked="(modelValue ?? []).includes(opt.value)"
                    @change="toggle(opt.value)"
                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 transition-all duration-150 focus:ring-2 focus:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-800 dark:checked:bg-indigo-500"
                />
            </span>
            <span
                class="text-sm transition-colors duration-150"
                :class="
                    (modelValue ?? []).includes(opt.value)
                        ? 'font-medium text-slate-900 dark:text-white'
                        : 'text-slate-600 dark:text-zinc-400'
                "
            >
                {{ opt.label }}
            </span>
        </label>
    </div>
</template>
