<!-- resources/js/Pages/Form/Components/FieldRenderer.vue -->
<script setup>
import { ref, computed, watch } from "vue";
import { validateField } from "@/utils/fieldValidation";
import TextField from "./inputs/TextField.vue";
import TextareaField from "./inputs/TextareaField.vue";
import DateField from "./inputs/DateField.vue";
import SelectField from "./inputs/SelectField.vue";
import RadioField from "./inputs/RadioField.vue";
import CheckboxField from "./inputs/CheckboxField.vue";
import FileField from "./inputs/FileField.vue";

const props = defineProps({
    field: Object,
    modelValue: [String, Number, Array, File, null],
    error: String,
    existingFile: Object,
});
const emit = defineEmits(["update:modelValue"]);

const localError = ref(null);
const touched = ref(false);
const currentValue = ref(props.modelValue);

watch(
    () => props.modelValue,
    (val) => {
        currentValue.value = val;
    },
);

const displayError = computed(() =>
    touched.value ? (localError.value ?? props.error) : props.error,
);

function runValidation() {
    localError.value = validateField(props.field, currentValue.value);
}

function handleUpdate(value) {
    currentValue.value = value;
    emit("update:modelValue", value);

    // Field beropsi (select/radio/checkbox) langsung tervalidasi begitu dipilih,
    // karena perubahan itu sendiri sudah jadi "keputusan" user, bukan sekadar mengetik.
    const instantTypes = ["select", "radio", "checkbox", "date", "file"];

    if (touched.value || instantTypes.includes(props.field.type)) {
        touched.value = true;
        runValidation();
    }
}

function handleBlur() {
    touched.value = true;
    runValidation();
}

const componentMap = {
    text: TextField,
    email: TextField,
    number: TextField,
    textarea: TextareaField,
    date: DateField,
    select: SelectField,
    radio: RadioField,
    checkbox: CheckboxField,
    file: FileField,
};

const spacedTypes = ["select", "radio", "checkbox", "file"];
const inputSpacingClass = computed(() =>
    spacedTypes.includes(props.field.type) ? "mt-3" : "mt-1.5",
);
</script>

<template>
    <div v-if="field.type === 'heading'" class="pt-2">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">
            {{ field.label }}
        </h2>
        <div class="mt-2 h-1 w-12 rounded-full bg-brand-gradient"></div>
    </div>

    <p
        v-else-if="field.type === 'paragraph'"
        class="text-sm leading-relaxed text-slate-600 dark:text-zinc-400"
    >
        {{ field.label }}
    </p>

    <div v-else class="scroll-mt-20" :id="`field-${field.name}`">
        <label class="block text-sm font-semibold text-slate-800 dark:text-white">
            {{ field.label }}
            <span
                v-if="field.is_required"
                class="font-bold text-rose-600 dark:text-rose-400"
                >*</span
            >
        </label>
        <p
            v-if="field.description"
            class="mt-1 text-sm text-slate-600 dark:text-zinc-400"
        >
            {{ field.description }}
        </p>

        <div :class="inputSpacingClass">
            <component
                :is="componentMap[field.type]"
                :field="field"
                :model-value="modelValue"
                :error="displayError"
                :existing-file="existingFile"
                @update:model-value="handleUpdate"
                @blur="handleBlur"
            />
        </div>

        <Transition
            enter-active-class="transition-all duration-150"
            enter-from-class="opacity-0 -translate-y-1"
        >
            <p
                v-if="displayError"
                class="mt-1.5 text-sm font-medium text-rose-600 dark:text-rose-400"
            >
                {{ displayError }}
            </p>
        </Transition>
    </div>
</template>
