<!-- resources/js/Pages/Form/Components/inputs/SelectField.vue -->
<script setup>
import { IconChevronDown } from "@tabler/icons-vue";

defineProps({ field: Object, modelValue: String, error: String });
defineEmits(["update:modelValue", "blur"]);
</script>

<template>
    <div class="relative w-full max-w-[50%] sm:max-w-xs">
        <select
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            @blur="$emit('blur')"
            class="w-full cursor-pointer appearance-none border-0 border-b bg-transparent bg-none px-0 py-1.5 pr-6 text-sm text-slate-900 outline-none transition-colors duration-200 ease-out focus:border-b-2 focus:ring-0 dark:text-white dark:[color-scheme:dark] [&::-ms-expand]:hidden"
            :class="[
                error
                    ? 'border-red-400 focus:border-red-500 dark:border-red-500/60 dark:focus:border-red-400'
                    : 'border-slate-300 focus:border-indigo-500 dark:border-zinc-700 dark:focus:border-indigo-400',
                !modelValue && 'text-slate-400 dark:text-zinc-500',
            ]"
        >
            <option
                value=""
                disabled
                class="bg-white text-slate-400 dark:bg-zinc-900 dark:text-zinc-500"
            >
                Pilih salah satu
            </option>
            <option
                v-for="opt in field.config?.options ?? []"
                :key="opt.value"
                :value="opt.value"
                class="bg-white text-slate-900 dark:bg-zinc-900 dark:text-white"
            >
                {{ opt.label }}
            </option>
        </select>

        <IconChevronDown
            :size="15"
            stroke="1.75"
            class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 dark:text-zinc-500"
        />
    </div>
</template>
