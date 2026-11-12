<!-- resources/js/Pages/Form/Components/inputs/DateField.vue -->
<script setup>
import DatePicker from "@/Components/DatePicker.vue";

const props = defineProps({ field: Object, modelValue: String, error: String });
const emit = defineEmits(["update:modelValue", "blur"]);

const displayFormat = props.field.config?.date_format || "DD/MM/YYYY";
</script>

<template>
    <DatePicker
        :model-value="modelValue"
        :display-format="displayFormat"
        :min-date="
            field.config?.validation?.operator
                ? field.config.validation.value
                : null
        "
        :max-date="
            field.config?.validation?.operator === 'between'
                ? field.config.validation.value2
                : null
        "
        :error="error"
        @update:model-value="$emit('update:modelValue', $event)"
        @blur="$emit('blur')"
    />
</template>
