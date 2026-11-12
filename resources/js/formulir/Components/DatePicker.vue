<!-- resources/js/Components/DatePicker.vue -->
<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import {
    IconChevronLeft,
    IconChevronRight,
    IconCalendar,
} from "@tabler/icons-vue";
import { formatDate, parseISO, toISO, MONTHS_ID } from "@/utils/dateFormat";

const props = defineProps({
    modelValue: String,
    displayFormat: { type: String, default: "DD/MM/YYYY" },
    minDate: String,
    maxDate: String,
    error: String,
    placeholder: { type: String, default: "Pilih tanggal" },
});
const emit = defineEmits(["update:modelValue", "blur"]);

const open = ref(false);
const rootEl = ref(null);

// 'days' | 'months' | 'years' — tingkatan navigasi kalender
const panelMode = ref("days");

const selectedDate = computed(() => parseISO(props.modelValue));
const viewDate = ref(selectedDate.value ?? new Date());

const displayText = computed(() =>
    selectedDate.value
        ? formatDate(selectedDate.value, props.displayFormat)
        : "",
);

const minD = computed(() => (props.minDate ? parseISO(props.minDate) : null));
const maxD = computed(() => (props.maxDate ? parseISO(props.maxDate) : null));

const calendarDays = computed(() => {
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const startOffset = firstDay.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const days = [];
    for (let i = 0; i < startOffset; i++) days.push(null);
    for (let d = 1; d <= daysInMonth; d++) days.push(new Date(year, month, d));
    return days;
});

// Rentang tahun untuk panel pilih tahun: 12 tahun per halaman, berpusat di tahun aktif
const yearRangeStart = ref(Math.floor(viewDate.value.getFullYear() / 12) * 12);
const yearRange = computed(() => {
    const years = [];
    for (let y = yearRangeStart.value; y < yearRangeStart.value + 12; y++)
        years.push(y);
    return years;
});

function isSameDay(a, b) {
    return (
        a &&
        b &&
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}
function isDisabled(date) {
    if (!date) return true;
    if (minD.value && date < minD.value) return true;
    if (maxD.value && date > maxD.value) return true;
    return false;
}
function isToday(date) {
    return isSameDay(date, new Date());
}

function selectDay(date) {
    if (isDisabled(date)) return;
    emit("update:modelValue", toISO(date));
    open.value = false;
    emit("blur");
}

function prevMonth() {
    viewDate.value = new Date(
        viewDate.value.getFullYear(),
        viewDate.value.getMonth() - 1,
        1,
    );
}
function nextMonth() {
    viewDate.value = new Date(
        viewDate.value.getFullYear(),
        viewDate.value.getMonth() + 1,
        1,
    );
}

function openMonthPanel() {
    panelMode.value = "months";
}
function openYearPanel() {
    yearRangeStart.value = Math.floor(viewDate.value.getFullYear() / 12) * 12;
    panelMode.value = "years";
}

function pickMonth(monthIndex) {
    viewDate.value = new Date(viewDate.value.getFullYear(), monthIndex, 1);
    panelMode.value = "days";
}
function pickYear(year) {
    viewDate.value = new Date(year, viewDate.value.getMonth(), 1);
    panelMode.value = "months";
}

function prevYearRange() {
    yearRangeStart.value -= 12;
}
function nextYearRange() {
    yearRangeStart.value += 12;
}

function toggleOpen() {
    open.value = !open.value;
    if (open.value) {
        viewDate.value = selectedDate.value ?? new Date();
        panelMode.value = "days";
    }
}

function handleClickOutside(e) {
    if (!rootEl.value) return;
    if (!rootEl.value.contains(e.target)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener("click", handleClickOutside));
onUnmounted(() => document.removeEventListener("click", handleClickOutside));

watch(open, (val) => {
    if (!val) emit("blur");
});
</script>

<template>
    <div ref="rootEl" class="relative w-full max-w-xs">
        <button
            type="button"
            @click="toggleOpen"
            class="flex w-full items-center justify-between border-0 border-b bg-transparent px-0 py-1.5 text-left text-sm text-slate-900 outline-none dark:text-white"
            :class="
                error
                    ? 'border-red-400 dark:border-red-500/60'
                    : 'border-slate-200 dark:border-zinc-800'
            "
        >
            <span
                :class="displayText ? '' : 'text-slate-400 dark:text-zinc-500'"
            >
                {{ displayText || placeholder }}
            </span>
            <IconCalendar
                :size="16"
                stroke="1.75"
                class="shrink-0 text-slate-400 dark:text-zinc-500"
            />
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            leave-active-class="transition duration-100 ease-in"
            enter-from-class="opacity-0 -translate-y-1"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-if="open"
                @click.stop
                class="absolute left-0 z-40 mt-2 w-72 rounded-2xl border border-slate-200 bg-white p-3 shadow-lift ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <!-- ============ PANEL: HARI ============ -->
                <template v-if="panelMode === 'days'">
                    <div class="mb-2 flex items-center justify-between">
                        <button
                            type="button"
                            @click="prevMonth"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                        >
                            <IconChevronLeft :size="16" />
                        </button>

                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="openMonthPanel"
                                class="rounded-md px-2 py-1 text-sm font-semibold text-slate-800 hover:bg-slate-100 dark:text-white dark:hover:bg-zinc-800"
                            >
                                {{ MONTHS_ID[viewDate.getMonth()] }}
                            </button>
                            <button
                                type="button"
                                @click="openYearPanel"
                                class="rounded-md px-2 py-1 text-sm font-semibold text-slate-800 hover:bg-slate-100 dark:text-white dark:hover:bg-zinc-800"
                            >
                                {{ viewDate.getFullYear() }}
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="nextMonth"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                        >
                            <IconChevronRight :size="16" />
                        </button>
                    </div>

                    <div
                        class="grid grid-cols-7 gap-1 text-center text-[11px] font-medium text-slate-400 dark:text-zinc-500"
                    >
                        <span
                            v-for="d in [
                                'Min',
                                'Sen',
                                'Sel',
                                'Rab',
                                'Kam',
                                'Jum',
                                'Sab',
                            ]"
                            :key="d"
                            >{{ d }}</span
                        >
                    </div>

                    <div class="mt-1 grid grid-cols-7 gap-1">
                        <button
                            v-for="(day, i) in calendarDays"
                            :key="i"
                            type="button"
                            :disabled="!day || isDisabled(day)"
                            @click="day && selectDay(day)"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-sm transition-colors"
                            :class="[
                                !day ? 'invisible' : '',
                                isDisabled(day)
                                    ? 'cursor-not-allowed text-slate-300 dark:text-zinc-700'
                                    : 'text-slate-700 hover:bg-indigo-50 dark:text-zinc-300 dark:hover:bg-indigo-500/10',
                                isSameDay(day, selectedDate)
                                    ? 'bg-indigo-600 text-white hover:bg-indigo-600 dark:bg-indigo-500'
                                    : '',
                                isToday(day) && !isSameDay(day, selectedDate)
                                    ? 'ring-1 ring-indigo-300 dark:ring-indigo-500/40'
                                    : '',
                            ]"
                        >
                            {{ day?.getDate() }}
                        </button>
                    </div>
                </template>

                <!-- ============ PANEL: BULAN ============ -->
                <template v-else-if="panelMode === 'months'">
                    <div class="mb-3 flex items-center justify-center">
                        <button
                            type="button"
                            @click="openYearPanel"
                            class="rounded-md px-3 py-1 text-sm font-semibold text-slate-800 hover:bg-slate-100 dark:text-white dark:hover:bg-zinc-800"
                        >
                            {{ viewDate.getFullYear() }}
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="(m, i) in MONTHS_ID"
                            :key="m"
                            type="button"
                            @click="pickMonth(i)"
                            class="rounded-lg py-2 text-sm transition-colors"
                            :class="
                                i === viewDate.getMonth()
                                    ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                    : 'text-slate-700 hover:bg-indigo-50 dark:text-zinc-300 dark:hover:bg-indigo-500/10'
                            "
                        >
                            {{ m.slice(0, 3) }}
                        </button>
                    </div>
                </template>

                <!-- ============ PANEL: TAHUN ============ -->
                <template v-else-if="panelMode === 'years'">
                    <div class="mb-3 flex items-center justify-between">
                        <button
                            type="button"
                            @click="prevYearRange"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                        >
                            <IconChevronLeft :size="16" />
                        </button>
                        <span
                            class="text-sm font-semibold text-slate-800 dark:text-white"
                        >
                            {{ yearRange[0] }} -
                            {{ yearRange[yearRange.length - 1] }}
                        </span>
                        <button
                            type="button"
                            @click="nextYearRange"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                        >
                            <IconChevronRight :size="16" />
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="y in yearRange"
                            :key="y"
                            type="button"
                            @click="pickYear(y)"
                            class="rounded-lg py-2 text-sm transition-colors"
                            :class="
                                y === viewDate.getFullYear()
                                    ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                    : 'text-slate-700 hover:bg-indigo-50 dark:text-zinc-300 dark:hover:bg-indigo-500/10'
                            "
                        >
                            {{ y }}
                        </button>
                    </div>
                </template>
            </div>
        </Transition>
    </div>
</template>
