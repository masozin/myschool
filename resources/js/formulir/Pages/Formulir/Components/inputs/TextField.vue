<!-- resources/js/Pages/Form/Components/inputs/TextField.vue -->
<script setup>
import { ref, computed, watch } from "vue";
import { parseFormatPattern, formatNumber } from "@/utils/numberFormat";

const props = defineProps({
    field: Object,
    modelValue: [String, Number],
    error: String,
});
const emit = defineEmits(["update:modelValue", "blur"]);

const isFormattedNumber = computed(
    () => props.field.type === "number" && !!props.field.config?.number_format,
);
const pattern = computed(() => props.field.config?.number_format ?? null);
const patternConfig = computed(() =>
    isFormattedNumber.value ? parseFormatPattern(pattern.value) : null,
);

const focused = ref(false);

// Saat fokus/mengetik: tampilkan ANGKA POLOS saja (tanpa prefix/suffix/grouping),
// supaya tidak ada karakter format yang ikut di-parse ulang tiap ketikan.
// Saat blur: tampilkan versi terformat penuh (grouping ribuan + desimal).
const local = ref(computeInitialDisplay());

function computeInitialDisplay() {
    if (isFormattedNumber.value) {
        return props.modelValue !== "" && props.modelValue != null
            ? String(props.modelValue)
            : "";
    }
    return props.modelValue ?? "";
}

watch(
    () => props.modelValue,
    (val) => {
        if (isFormattedNumber.value) {
            if (focused.value) return; // jangan timpa saat user masih mengetik
            local.value =
                val !== "" && val != null
                    ? formatNumber(val, pattern.value)
                    : "";
        } else if (val !== local.value) {
            local.value = val ?? "";
        }
    },
);

function onInput(e) {
    if (isFormattedNumber.value) {
        // Hanya izinkan digit dan SATU pemisah desimal sesuai pola (misal ",")
        const decSep = patternConfig.value?.decimalSep || ".";
        const escapedSep = decSep.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        const allowedPattern = new RegExp(`[^0-9${escapedSep}]`, "g");

        let raw = e.target.value.replace(allowedPattern, "");

        // Cegah lebih dari satu pemisah desimal
        const firstSepIndex = raw.indexOf(decSep);
        if (firstSepIndex !== -1) {
            raw =
                raw.slice(0, firstSepIndex + 1) +
                raw
                    .slice(firstSepIndex + 1)
                    .split(decSep)
                    .join("");
        }

        local.value = raw;
        e.target.value = raw; // sinkronkan langsung ke DOM, cegah karakter tak valid sempat tampil

        const numeric =
            raw === "" || raw === decSep
                ? ""
                : parseFloat(raw.replace(decSep, "."));
        emit(
            "update:modelValue",
            numeric === "" || isNaN(numeric) ? "" : numeric,
        );
        return;
    }

    local.value = e.target.value;
    emit("update:modelValue", local.value);
}

function onFocus() {
    if (isFormattedNumber.value) {
        focused.value = true;
        // Balik ke angka polos untuk diedit (tanpa prefix/suffix/grouping)
        local.value =
            props.modelValue !== "" && props.modelValue != null
                ? String(props.modelValue).replace(
                      ".",
                      patternConfig.value?.decimalSep || ".",
                  )
                : "";
    }
}

function onBlur() {
    if (isFormattedNumber.value) {
        focused.value = false;
        local.value =
            props.modelValue !== "" && props.modelValue != null
                ? formatNumber(props.modelValue, pattern.value)
                : "";
    }
    emit("blur");
}

function onWheel(e) {
    if (props.field.type === "number") {
        e.target.blur();
    }
}
</script>

<template>
    <div class="group relative w-full max-w-[50%] sm:max-w-xs">
        <div
            class="flex items-center border-b"
            :class="
                error
                    ? 'border-red-400 dark:border-red-500/60'
                    : 'border-slate-200 dark:border-zinc-800'
            "
        >
            <input
                :type="
                    isFormattedNumber
                        ? 'text'
                        : field.type === 'email'
                          ? 'email'
                          : field.type === 'number'
                            ? 'number'
                            : 'text'
                "
                :inputmode="isFormattedNumber ? 'decimal' : undefined"
                :value="local"
                :placeholder="field.config?.placeholder || 'Jawaban Anda'"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                @wheel="onWheel"
                class="w-full border-0 bg-transparent px-0 py-1.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:ring-0 dark:text-white dark:placeholder:text-zinc-500 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
            />
        </div>

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
