<!-- FieldCard.vue -->
<script setup>
import { computed } from "vue";
import InlineEditableTextarea from "@/Components/InlineEditableTextarea.vue";
import { formatNumber } from "@/utils/numberFormat";
import { formatDate } from "@/utils/dateFormat";
import {
    IconGripVertical,
    IconTrash,
    IconCopy,
    IconChevronDown,
    IconChevronUp,
    IconEyeOff,
    IconEye,
    IconAdjustmentsHorizontal,
    IconPlus,
    IconArrowUp,
    IconArrowDown,
} from "@tabler/icons-vue";

const props = defineProps({
    field: Object,
    isFirst: { type: Boolean, default: false },
    isLast: { type: Boolean, default: false },
});
const emit = defineEmits([
    "remove",
    "duplicate",
    "move-up",
    "move-down",
    "open-picker",
]);

const TYPE_OPTIONS = [
    { value: "text", label: "Teks Singkat" },
    { value: "textarea", label: "Teks Panjang" },
    { value: "number", label: "Angka" },
    { value: "select", label: "Dropdown" },
    { value: "radio", label: "Pilihan Tunggal" },
    { value: "checkbox", label: "Pilihan Ganda" },
    { value: "date", label: "Tanggal" },
    { value: "email", label: "Email" },
    { value: "file", label: "Unggah File" },
    { value: "heading", label: "Judul Bagian" },
    { value: "paragraph", label: "Teks Penjelasan" },
];

const NUMBER_OPERATORS = [
    { value: "equals", label: "Sama dengan" },
    { value: "not_equals", label: "Tidak sama dengan" },
    { value: "gt", label: "Lebih dari" },
    { value: "lt", label: "Kurang dari" },
    { value: "between", label: "Di antara" },
    { value: "not_between", label: "Bukan di antara" },
];

const numberFormatPreview = computed(() => {
    if (!props.field.config?.number_format) return null;
    return formatNumber(1234567.5, props.field.config.number_format);
});

const DATE_FORMAT_PRESETS = [
    { value: "DD/MM/YYYY", label: "31/12/2026" },
    { value: "DD-MM-YYYY", label: "31-12-2026" },
    { value: "D MMMM YYYY", label: "31 Desember 2026" },
    { value: "dddd, D MMMM YYYY", label: "Kamis, 31 Desember 2026" },
    { value: "YYYY-MM-DD", label: "2026-12-31" },
];

const FILE_EXTENSIONS = [
    { value: "pdf", label: "PDF" },
    { value: "doc", label: "DOC" },
    { value: "docx", label: "DOCX" },
    { value: "xls", label: "XLS" },
    { value: "xlsx", label: "XLSX" },
    { value: "jpg", label: "JPG" },
    { value: "jpeg", label: "JPEG" },
    { value: "png", label: "PNG" },
];

const needsOptions = computed(() =>
    ["select", "radio", "checkbox"].includes(props.field.type),
);
const needsValidation = computed(() =>
    ["text", "textarea", "number", "date", "file"].includes(props.field.type),
);
const isDisplayOnly = computed(() =>
    ["heading", "paragraph"].includes(props.field.type),
);
const isRangeOperator = computed(() =>
    ["between", "not_between"].includes(
        props.field.config?.validation?.operator,
    ),
);
const isNotEqualsOperator = computed(
    () => props.field.config?.validation?.operator === "not_equals",
);
const inputType = computed(() =>
    props.field.type === "date" ? "date" : "number",
);

function addOption() {
    if (!props.field.config.options) props.field.config.options = [];
    const n = props.field.config.options.length + 1;
    props.field.config.options.push({ label: `Opsi ${n}`, value: `opsi_${n}` });
}
function removeOption(i) {
    props.field.config.options.splice(i, 1);
}
function syncValue(opt) {
    opt.value =
        opt.label
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, "_") || opt.value;
}

function toggleValidation() {
    props.field._showValidation = !props.field._showValidation;
    if (props.field._showValidation && !props.field.config.validation) {
        props.field.config.validation = {};
    }
}

function onOperatorChange() {
    const v = props.field.config.validation;
    delete v.value2;
    delete v.values;
    if (v.operator === "not_equals") v.values = "";
    else v.value = "";
}

// ---- Validasi text/textarea: daftar aturan (length, contains, not_contains) ----
const TEXT_RULE_TYPES = [
    { value: "length", label: "Jumlah Karakter" },
    { value: "contains", label: "Harus Berisi" },
    { value: "not_contains", label: "Tidak Boleh Berisi" },
];

const availableTextRuleTypes = computed(() => {
    const usedTypes = (props.field.config?.validation?.rules ?? []).map(
        (r) => r.type,
    );
    return TEXT_RULE_TYPES.filter((rt) => !usedTypes.includes(rt.value));
});

function ensureTextRules() {
    if (!props.field.config.validation) props.field.config.validation = {};
    if (!props.field.config.validation.rules)
        props.field.config.validation.rules = [];
}

function addTextRule(type) {
    ensureTextRules();
    const rule =
        type === "length"
            ? { type: "length", min: null, max: null }
            : { type, value: "" };
    props.field.config.validation.rules.push(rule);
}

function removeTextRule(i) {
    props.field.config.validation.rules.splice(i, 1);
}

// ---- Validasi file: ukuran & ekstensi, masing-masing independen bisa dihapus ----
function ensureFileValidation() {
    if (!props.field.config.validation) props.field.config.validation = {};
}

function addFileSizeRule() {
    ensureFileValidation();
    props.field.config.validation.max_size = 5;
}
function removeFileSizeRule() {
    delete props.field.config.validation.max_size;
}

function addFileTypeRule() {
    ensureFileValidation();
    props.field.config.validation.extensions = [];
}
function removeFileTypeRule() {
    delete props.field.config.validation.extensions;
}
function toggleExtension(ext) {
    const list = props.field.config.validation.extensions;
    const idx = list.indexOf(ext);
    if (idx > -1) list.splice(idx, 1);
    else list.push(ext);
}
</script>

<template>
    <div
        class="group/card relative rounded-2xl border bg-white shadow-card ring-1 ring-slate-900/5 transition-all duration-150 dark:bg-zinc-900"
        :class="
            field._hidden
                ? 'border-slate-300 opacity-60 dark:border-zinc-800'
                : 'border-slate-300 dark:border-zinc-800'
        "
    >
        <!-- Toolbar hover kanan -->
        <div
            class="absolute -right-11 top-0 hidden flex-col gap-1 opacity-0 transition-opacity duration-150 group-hover/card:opacity-100 sm:flex"
        >
            <button
                :disabled="isFirst"
                @click="$emit('move-up')"
                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400 transition-colors hover:border-indigo-500 hover:text-indigo-600 disabled:opacity-30 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-500 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
            >
                <IconArrowUp :size="15" />
            </button>
            <button
                :disabled="isLast"
                @click="$emit('move-down')"
                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400 transition-colors hover:border-indigo-500 hover:text-indigo-600 disabled:opacity-30 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-500 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
            >
                <IconArrowDown :size="15" />
            </button>
            <button
                @click="$emit('open-picker')"
                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400 transition-colors hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-500 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                title="Tambah pertanyaan atau bagian di bawah ini"
            >
                <IconPlus :size="15" />
            </button>
        </div>

        <!-- Bar atas -->
        <div class="flex items-center gap-2 rounded-t-2xl border-b border-slate-200 bg-slate-50/90 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
            <IconGripVertical
                :size="16"
                class="drag-handle shrink-0 cursor-grab text-slate-400 opacity-80 transition hover:text-slate-600 dark:text-zinc-600 dark:hover:text-zinc-400"
            />
            <span
                class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-900 dark:text-white"
            >
                {{ field.label || "Pertanyaan tanpa judul" }}
            </span>
            <span
                class="hidden shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 sm:block"
                :class="
                    ['select', 'radio', 'checkbox'].includes(field.type)
                        ? 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30'
                        : field.type === 'file'
                          ? 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30'
                          : ['heading', 'paragraph'].includes(field.type)
                            ? 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700'
                            : 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/30'
                "
            >
                {{ TYPE_OPTIONS.find((t) => t.value === field.type)?.label }}
            </span>
            <div class="flex shrink-0 items-center gap-0.5">
                <button
                    @click="$emit('move-up')"
                    :disabled="isFirst"
                    class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-900 disabled:opacity-30 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-white sm:hidden"
                >
                    <IconArrowUp :size="15" />
                </button>
                <button
                    @click="$emit('move-down')"
                    :disabled="isLast"
                    class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-900 disabled:opacity-30 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-white sm:hidden"
                >
                    <IconArrowDown :size="15" />
                </button>
                <button
                    @click="field._hidden = !field._hidden"
                    class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-white"
                >
                    <component
                        :is="field._hidden ? IconEye : IconEyeOff"
                        :size="16"
                    />
                </button>
                <button
                    @click="$emit('duplicate')"
                    class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-white"
                >
                    <IconCopy :size="16" />
                </button>
                <button
                    @click="$emit('remove')"
                    class="rounded p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-500 dark:text-zinc-500 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                >
                    <IconTrash :size="16" />
                </button>
                <button
                    @click="field._collapsed = !field._collapsed"
                    class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-white"
                >
                    <component
                        :is="field._collapsed ? IconChevronDown : IconChevronUp"
                        :size="16"
                    />
                </button>
            </div>
        </div>

        <div
            v-show="!field._collapsed"
            class="space-y-2 px-4 pb-4 pt-4"
        >
            <div class="flex flex-col gap-3 sm:flex-row">
                <input
                    v-model="field.label"
                    placeholder="Tulis pertanyaan..."
                    class="flex-1 rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500"
                />
                <select
                    v-model="field.type"
                    class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white sm:w-48"
                >
                    <option
                        v-for="t in TYPE_OPTIONS"
                        :key="t.value"
                        :value="t.value"
                    >
                        {{ t.label }}
                    </option>
                </select>
            </div>

            <InlineEditableTextarea
                v-if="!isDisplayOnly"
                v-model="field.description"
                placeholder="Tambah keterangan..."
                text-class="text-sm text-slate-500 dark:text-zinc-400"
            />

            <!-- Opsi jawaban -->
            <div
                v-if="needsOptions"
                class="space-y-2 rounded-lg bg-slate-50 p-3 dark:bg-zinc-800/50"
            >
                <label
                    class="text-xs font-semibold text-slate-500 dark:text-zinc-400"
                >
                    Pilihan Jawaban
                </label>
                <div
                    v-for="(opt, i) in field.config.options"
                    :key="i"
                    class="flex items-center gap-2"
                >
                    <input
                        v-model="opt.label"
                        @input="syncValue(opt)"
                        class="flex-1 rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    />
                    <button
                        @click="removeOption(i)"
                        class="text-slate-400 hover:text-red-500 dark:text-zinc-500 dark:hover:text-red-400"
                    >
                        <IconTrash :size="15" />
                    </button>
                </div>
                <button
                    @click="addOption"
                    class="flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    <IconPlus :size="14" /> Tambah Opsi
                </button>
            </div>

            <!-- ============ BLOK VALIDASI ============ -->
            <div
                v-if="field._showValidation && needsValidation"
                class="space-y-3 rounded-lg bg-slate-50 p-3 dark:bg-zinc-800/50"
            >
                <!-- TEXT / TEXTAREA -->
                <template v-if="['text', 'textarea'].includes(field.type)">
                    <div
                        v-for="(rule, i) in field.config.validation?.rules ??
                        []"
                        :key="i"
                        class="rounded-md border border-slate-200 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <span
                                class="text-xs font-semibold text-slate-900 dark:text-white"
                            >
                                {{
                                    TEXT_RULE_TYPES.find(
                                        (t) => t.value === rule.type,
                                    )?.label
                                }}
                            </span>
                            <button
                                @click="removeTextRule(i)"
                                class="text-slate-400 hover:text-red-500 dark:text-zinc-500 dark:hover:text-red-400"
                            >
                                <IconTrash :size="14" />
                            </button>
                        </div>

                        <div
                            v-if="rule.type === 'length'"
                            class="grid grid-cols-2 gap-2"
                        >
                            <div>
                                <label
                                    class="text-xs text-slate-500 dark:text-zinc-400"
                                >
                                    Minimum
                                </label>
                                <input
                                    v-model.number="rule.min"
                                    type="number"
                                    class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label
                                    class="text-xs text-slate-500 dark:text-zinc-400"
                                >
                                    Maksimum
                                </label>
                                <input
                                    v-model.number="rule.max"
                                    type="number"
                                    class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div v-else>
                            <input
                                v-model="rule.value"
                                type="text"
                                placeholder="cth: kata1;kata2|kata3"
                                class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500"
                            />
                            <p
                                class="mt-1 text-xs text-slate-500 dark:text-zinc-400"
                            >
                                Pisahkan dengan
                                <span class="font-mono">;</span> untuk
                                <strong>dan</strong>,
                                <span class="font-mono">|</span> untuk
                                <strong>atau</strong>.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="rt in availableTextRuleTypes"
                            :key="rt.value"
                            @click="addTextRule(rt.value)"
                            class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-500 transition-colors hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                        >
                            <IconPlus :size="12" /> {{ rt.label }}
                        </button>
                    </div>
                </template>

                <!-- NUMBER / DATE -->
                <template v-else-if="['number', 'date'].includes(field.type)">
                    <div
                        class="rounded-md border border-slate-200 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <span
                                class="text-xs font-semibold text-slate-900 dark:text-white"
                            >
                                Aturan Nilai
                            </span>
                            <button
                                @click="field.config.validation = {}"
                                class="text-slate-400 hover:text-red-500 dark:text-zinc-500 dark:hover:text-red-400"
                            >
                                <IconTrash :size="14" />
                            </button>
                        </div>
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-end"
                        >
                            <div class="flex-1">
                                <label
                                    class="text-xs text-slate-500 dark:text-zinc-400"
                                >
                                    Aturan
                                </label>
                                <select
                                    v-model="field.config.validation.operator"
                                    @change="onOperatorChange"
                                    class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                >
                                    <option
                                        v-for="op in NUMBER_OPERATORS"
                                        :key="op.value"
                                        :value="op.value"
                                    >
                                        {{ op.label }}
                                    </option>
                                </select>
                            </div>
                            <div
                                v-if="!isRangeOperator && !isNotEqualsOperator"
                                class="flex-1"
                            >
                                <label
                                    class="text-xs text-slate-500 dark:text-zinc-400"
                                >
                                    Nilai
                                </label>
                                <input
                                    v-model="field.config.validation.value"
                                    :type="inputType"
                                    class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                />
                            </div>
                            <div v-if="isNotEqualsOperator" class="flex-1">
                                <label
                                    class="text-xs text-slate-500 dark:text-zinc-400"
                                >
                                    Nilai (pisahkan dengan ;)
                                </label>
                                <input
                                    v-model="field.config.validation.values"
                                    type="text"
                                    placeholder="cth: 1;2;3"
                                    class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500"
                                />
                            </div>
                            <template v-if="isRangeOperator">
                                <div class="flex-1">
                                    <label
                                        class="text-xs text-slate-500 dark:text-zinc-400"
                                    >
                                        Dari
                                    </label>
                                    <input
                                        v-model="field.config.validation.value"
                                        :type="inputType"
                                        class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label
                                        class="text-xs text-slate-500 dark:text-zinc-400"
                                    >
                                        Sampai
                                    </label>
                                    <input
                                        v-model="field.config.validation.value2"
                                        :type="inputType"
                                        class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                    />
                                </div>
                            </template>
                        </div>
                    </div>
                    <div
                        v-if="field.type === 'number'"
                        class="rounded-md border border-slate-200 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <div class="flex items-center justify-between">
                            <label
                                class="text-xs font-semibold text-slate-900 dark:text-white"
                                >Format Tampilan Angka</label
                            >
                            <button
                                v-if="field.config.number_format"
                                @click="field.config.number_format = null"
                                class="text-muted hover:text-sienna"
                            >
                                <IconTrash :size="14" />
                            </button>
                        </div>
                        <input
                            v-model="field.config.number_format"
                            type="text"
                            placeholder="Contoh: Rp#.###,00"
                            class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white font-mono"
                        />
                        <p class="mt-1 text-xs text-muted">
                            <code>#</code> = digit, <code>.</code>/<code
                                >,</code
                            >
                            menentukan pemisah ribuan & desimal.
                        </p>
                        <p
                            v-if="numberFormatPreview"
                            class="mt-1.5 text-xs text-emerald dark:text-zinc-400"
                        >
                            Contoh tampilan:
                            <strong>{{ numberFormatPreview }}</strong>
                        </p>
                    </div>
                    <div
                        v-if="field.type === 'date'"
                        class="rounded-md border border-slate-200 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <label
                            class="text-xs font-medium text-slate-900 dark:text-white"
                            >Format Tampilan Tanggal</label
                        >
                        <select
                            v-model="field.config.date_format"
                            class="w-full mt-1.5 rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        >
                            <option
                                v-for="p in DATE_FORMAT_PRESETS"
                                :key="p.value"
                                :value="p.value"
                            >
                                {{ p.label }}
                            </option>
                        </select>
                        <p
                            v-if="dateFormatPreview"
                            class="mt-1.5 text-xs text-emerald"
                        >
                            Contoh tampilan:
                            <strong>{{ dateFormatPreview }}</strong>
                        </p>
                    </div>
                </template>

                <!-- FILE -->
                <template v-else-if="field.type === 'file'">
                    <div
                        v-if="field.config.validation?.max_size !== undefined"
                        class="rounded-md border border-slate-200 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <span
                                class="text-xs font-semibold text-slate-900 dark:text-white"
                            >
                                Ukuran Maksimum
                            </span>
                            <button
                                @click="removeFileSizeRule"
                                class="text-slate-400 hover:text-red-500 dark:text-zinc-500 dark:hover:text-red-400"
                            >
                                <IconTrash :size="14" />
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="
                                    field.config.validation.max_size
                                "
                                type="number"
                                min="1"
                                class="w-24 rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                            />
                            <span
                                class="text-sm text-slate-500 dark:text-zinc-400"
                                >MB</span
                            >
                        </div>
                    </div>

                    <div
                        v-if="field.config.validation?.extensions"
                        class="rounded-md border border-slate-200 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <span
                                class="text-xs font-semibold text-slate-900 dark:text-white"
                            >
                                Jenis File Diizinkan
                            </span>
                            <button
                                @click="removeFileTypeRule"
                                class="text-slate-400 hover:text-red-500 dark:text-zinc-500 dark:hover:text-red-400"
                            >
                                <IconTrash :size="14" />
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="ext in FILE_EXTENSIONS"
                                :key="ext.value"
                                @click="toggleExtension(ext.value)"
                                class="rounded-full border px-2.5 py-1 text-xs font-medium transition-colors"
                                :class="
                                    field.config.validation.extensions.includes(
                                        ext.value,
                                    )
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-600 dark:border-indigo-400 dark:bg-indigo-500/10 dark:text-indigo-400'
                                        : 'border-slate-200 text-slate-500 hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-indigo-400 dark:hover:text-indigo-400'
                                "
                            >
                                {{ ext.label }}
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="
                                field.config.validation?.max_size === undefined
                            "
                            @click="addFileSizeRule"
                            class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-500 transition-colors hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                        >
                            <IconPlus :size="12" /> Ukuran Maksimum
                        </button>
                        <button
                            v-if="!field.config.validation?.extensions"
                            @click="addFileTypeRule"
                            class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-500 transition-colors hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                        >
                            <IconPlus :size="12" /> Jenis File
                        </button>
                    </div>

                    <div
                        v-if="field.type === 'file'"
                        class="rounded-md border border-border bg-surface p-2.5"
                    >
                        <label class="text-xs font-medium text-ink"
                            >Rasio Crop Gambar</label
                        >
                        <select
                            v-model="field.config.crop_ratio"
                            class="mt-1.5 w-full rounded-md border-border bg-surface text-sm"
                        >
                            <option value="none">Tanpa crop</option>
                            <option value="free">
                                Bebas (rasio bisa disesuaikan)
                            </option>
                            <option value="1">1:1 (Persegi)</option>
                            <option value="1.333">4:3</option>
                            <option value="1.778">16:9</option>
                            <option value="0.75">3:4 (Potret)</option>
                        </select>
                    </div>
                </template>
            </div>

            <div
                class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3 dark:border-zinc-800"
            >
                <button
                    v-if="needsValidation"
                    @click="toggleValidation"
                    class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                >
                    <IconAdjustmentsHorizontal :size="15" />
                    {{
                        field._showValidation
                            ? "Sembunyikan Validasi"
                            : "Format & Validasi"
                    }}
                </button>
                <label
                    v-if="!isDisplayOnly"
                    class="flex cursor-pointer items-center gap-2 rounded-full bg-rose-50 py-1 pl-2.5 pr-3 text-xs font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30"
                >
                    <input
                        v-model="field.is_required"
                        type="checkbox"
                        class="rounded border-rose-300 text-rose-600 focus:ring-rose-500/30 dark:border-rose-500/40 dark:bg-zinc-800"
                    />
                    Wajib diisi
                </label>
            </div>
        </div>
    </div>
</template>
