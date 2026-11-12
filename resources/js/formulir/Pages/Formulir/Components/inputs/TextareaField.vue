<!-- resources/js/Pages/Form/Components/inputs/TextareaField.vue -->
<script setup>
import { ref, watch, nextTick, onMounted } from "vue";

const props = defineProps({ field: Object, modelValue: String, error: String });
const emit = defineEmits(["update:modelValue", "blur"]);

const local = ref(props.modelValue ?? "");
const el = ref(null);

watch(
    () => props.modelValue,
    (val) => {
        if (val !== local.value) {
            local.value = val ?? "";
            nextTick(resize);
        }
    },
);

function resize() {
    if (!el.value) return;
    el.value.style.height = "auto";
    el.value.style.height = `${el.value.scrollHeight}px`;
}

function onInput(e) {
    local.value = e.target.value;
    emit("update:modelValue", local.value);
    resize();
}

onMounted(resize);
</script>

<template>
    <div class="group relative w-full">
        <textarea
            ref="el"
            :value="local"
            :placeholder="field.config?.placeholder || 'Jawaban Anda'"
            rows="1"
            @input="onInput"
            @blur="$emit('blur')"
            class="block w-full resize-none overflow-hidden border-0 border-b bg-transparent px-0 py-1.5 text-sm leading-relaxed text-slate-900 outline-none placeholder:text-slate-500 focus:ring-0 dark:text-white dark:placeholder:text-zinc-500"
            :class="
                error
                    ? 'border-red-400 dark:border-red-500/60'
                    : 'border-slate-200 dark:border-zinc-800'
            "
        ></textarea>

        <!-- Garis animasi dari tengah -->
        <div
            class="absolute bottom-0 left-1/2 h-[2px] w-full -translate-x-1/2 scale-x-0 transition-all duration-200 ease-out group-focus-within:scale-x-100"
            :class="
                error
                    ? 'bg-red-500 dark:bg-red-400'
                    : 'bg-indigo-500 dark:bg-indigo-400'
            "
        ></div>
    </div>
</template>
