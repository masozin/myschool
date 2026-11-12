<!-- resources/js/Components/InlineEditableTextarea.vue -->
<script setup>
import { ref, nextTick, watch } from "vue";
import {
    IconBold,
    IconItalic,
    IconUnderline,
    IconStrikethrough,
} from "@tabler/icons-vue";

const props = defineProps({
    modelValue: String,
    placeholder: { type: String, default: "Klik untuk isi..." },
    textClass: { type: String, default: "text-sm text-ink" },
    maxHeight: { type: Number, default: 160 }, // px, setelah ini baru scroll
});
const emit = defineEmits(["update:modelValue"]);

const focused = ref(false);
const textareaRef = ref(null);

function resize() {
    const el = textareaRef.value;
    if (!el) return;
    el.style.height = "auto";
    const next = Math.min(el.scrollHeight, props.maxHeight);
    el.style.height = `${next}px`;
    el.style.overflowY = el.scrollHeight > props.maxHeight ? "auto" : "hidden";
}

watch(
    () => props.modelValue,
    () => nextTick(resize),
);

async function onFocus() {
    focused.value = true;
    await nextTick();
    resize();
}

function onBlur() {
    // beri jeda kecil supaya klik tombol toolbar tidak langsung memicu blur duluan
    setTimeout(() => {
        focused.value = false;
    }, 120);
}

function wrapSelection(before, after = before) {
    const el = textareaRef.value;
    if (!el) return;
    const { selectionStart: start, selectionEnd: end } = el;
    const value = props.modelValue || "";
    const selected = value.slice(start, end);
    const newValue =
        value.slice(0, start) + before + selected + after + value.slice(end);
    emit("update:modelValue", newValue);

    nextTick(() => {
        el.focus();
        el.setSelectionRange(start + before.length, end + before.length);
        resize();
    });
}

const format = {
    bold: () => wrapSelection("**"),
    italic: () => wrapSelection("*"),
    underline: () => wrapSelection("__"),
    strike: () => wrapSelection("~~"),
};
</script>

<template>
    <div>
        <div class="relative">
            <textarea
                ref="textareaRef"
                :value="modelValue"
                :placeholder="placeholder"
                rows="1"
                @input="
                    emit('update:modelValue', $event.target.value);
                    resize();
                "
                @focus="onFocus"
                @blur="onBlur"
                class="w-full resize-none border-0 border-b bg-transparent px-0 py-1 leading-relaxed transition-colors duration-150 focus:ring-0"
                :class="[
                    textClass,
                    focused ? 'border-transparent' : 'border-transparent',
                ]"
                style="overflow-y: hidden"
            ></textarea>

            <!-- animasi saat fokus -->
            <div
                class="pointer-events-none absolute bottom-0 left-0 h-px bg-brand transition-colors duration-300 ease-out"
            ></div>
        </div>

        <!-- Toolbar format: muncul saat fokus, sembunyi saat blur -->
        <Transition
            enter-active-class="transition-all duration-150 ease-out"
            leave-active-class="transition-all duration-100 ease-in"
            enter-from-class="opacity-0 -translate-y-1"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div v-if="focused" class="mt-0 flex items-center gap-0.5">
                <button
                    type="button"
                    @mousedown.prevent="format.bold"
                    class="flex h-7 w-7 items-center justify-center rounded text-muted hover:bg-canvas dark:bg-zinc-900 hover:text-ink"
                    title="Tebal"
                >
                    <IconBold :size="15" />
                </button>
                <button
                    type="button"
                    @mousedown.prevent="format.italic"
                    class="flex h-7 w-7 items-center justify-center rounded text-muted hover:bg-canvas dark:bg-zinc-900 hover:text-ink"
                    title="Miring"
                >
                    <IconItalic :size="15" />
                </button>
                <button
                    type="button"
                    @mousedown.prevent="format.underline"
                    class="flex h-7 w-7 items-center justify-center rounded text-muted hover:bg-canvas dark:bg-zinc-900 hover:text-ink"
                    title="Garis bawah"
                >
                    <IconUnderline :size="15" />
                </button>
                <button
                    type="button"
                    @mousedown.prevent="format.strike"
                    class="flex h-7 w-7 items-center justify-center rounded text-muted hover:bg-canvas dark:bg-zinc-900 hover:text-ink"
                    title="Coret"
                >
                    <IconStrikethrough :size="15" />
                </button>
            </div>
        </Transition>
    </div>
</template>
