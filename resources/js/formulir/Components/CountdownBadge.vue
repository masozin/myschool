<!-- resources/js/Components/CountdownBadge.vue -->
<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { IconClock } from "@tabler/icons-vue";

const props = defineProps({
    endAt: String,
});

const now = ref(Date.now());
let timer = null;

onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now();
    }, 1000);
});
onUnmounted(() => clearInterval(timer));

const remaining = computed(() => {
    const diff = new Date(props.endAt).getTime() - now.value;
    return Math.max(diff, 0);
});

const isUrgent = computed(
    () => remaining.value > 0 && remaining.value < 1000 * 60 * 60,
); // < 1 jam
const isClosed = computed(() => remaining.value <= 0);

function pad(n) {
    return String(n).padStart(2, "0");
}

const label = computed(() => {
    if (isClosed.value) return "Ditutup";

    const totalSeconds = Math.floor(remaining.value / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    // Detik selalu ditampilkan supaya kelihatan berjalan (tick tiap detik)
    if (days > 0)
        return `${days}h ${pad(hours)}:${pad(minutes)}:${pad(seconds)} lagi`;
    if (hours > 0) return `${pad(hours)}:${pad(minutes)}:${pad(seconds)} lagi`;
    return `${pad(minutes)}:${pad(seconds)} lagi`;
});
</script>

<template>
    <div
        class="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold tabular-nums shadow-sm transition-colors"
        :class="
            isClosed
                ? 'border border-slate-300 bg-slate-200/70 text-slate-600'
                : isUrgent
                  ? 'bg-rose-600 text-white animate-pulse'
                  : 'border border-amber-200 bg-amber-50 text-amber-800'
        "
    >
        <IconClock :size="13" stroke="2" />
        {{ label }}
    </div>
</template>
