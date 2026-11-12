<!-- resources/js/Components/InlineEditableText.vue -->
<script setup>
import { ref, nextTick } from "vue";

const props = defineProps({
    modelValue: String,
    placeholder: { type: String, default: "Klik untuk isi..." },
    tag: { type: String, default: "div" }, // div untuk title besar, span untuk teks kecil
    textClass: { type: String, default: "" },
});
const emit = defineEmits(["update:modelValue"]);

const editing = ref(false);
const inputRef = ref(null);

async function startEdit() {
    editing.value = true;
    await nextTick();
    inputRef.value?.focus();
}

function stopEdit() {
    editing.value = false;
}
</script>

<template>
    <input
        v-if="editing"
        ref="inputRef"
        :value="modelValue"
        @input="emit('update:modelValue', $event.target.value)"
        @blur="stopEdit"
        @keydown.enter="stopEdit"
        :placeholder="placeholder"
        class="w-full border-0 border-b border-brand bg-transparent px-0 py-0.5 focus:border-brand focus:ring-0"
        :class="textClass"
    />
    <component
        :is="tag"
        v-else
        @click="startEdit"
        class="cursor-text rounded px-0 py-0.5 border-b border-transparent hover:border-border dark:border-border-dark/60 transition-colors"
        :class="[textClass, !modelValue && 'text-muted/60']"
    >
        {{ modelValue || placeholder }}
    </component>
</template>
