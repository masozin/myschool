<!-- resources/js/Pages/Form/Closed.vue -->
<script setup>
import FormLayout from "@/Layouts/FormLayout.vue";
import {
    IconClockPause,
    IconLock,
    IconFileOff,
    IconCalendarTime,
} from "@tabler/icons-vue";
import { computed } from "vue";

const props = defineProps({
    form: Object,
    reason: String, // not_published | not_started | ended | closed
});

function formatDate(d) {
    return new Date(d).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

const config = computed(
    () =>
        ({
            not_published: {
                icon: IconFileOff,
                title: "Form belum tersedia",
                message:
                    "Form ini masih dalam tahap persiapan dan belum dibuka untuk umum. Silakan hubungi pengelola form untuk informasi lebih lanjut.",
            },
            not_started: {
                icon: IconCalendarTime,
                title: "Form belum dibuka",
                message: props.form.start_at
                    ? `Pengisian form ini akan dibuka mulai ${formatDate(props.form.start_at)}.`
                    : "Pengisian form ini belum dibuka.",
            },
            ended: {
                icon: IconClockPause,
                title: "Periode pengisian telah berakhir",
                message: props.form.end_at
                    ? `Form ini ditutup sejak ${formatDate(props.form.end_at)}. Hubungi pengelola form jika kamu memerlukan akses tambahan.`
                    : "Periode pengisian form ini sudah berakhir.",
            },
            closed: {
                icon: IconLock,
                title: "Form ditutup",
                message:
                    "Pengelola telah menutup form ini secara manual dan tidak menerima jawaban baru saat ini.",
            },
        })[props.reason] ?? {
            icon: IconLock,
            title: "Form tidak dapat diakses",
            message: "Form ini sedang tidak tersedia untuk diisi.",
        },
);
</script>

<template>
    <FormLayout>
        <template #banner>
            <div
                v-if="form.banner_image"
                class="relative h-40 w-full overflow-hidden opacity-60 grayscale sm:h-52"
            >
                <img
                    :src="`/storage/${form.banner_image}`"
                    class="h-full w-full object-cover"
                    :alt="form.title"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-slate-950/20 to-transparent"
                ></div>
            </div>
        </template>

        <!-- Header: tetap tampilkan info form apa adanya -->
        <div class="mb-6">
            <h1
                class="text-2xl font-bold tracking-[-0.01em] text-slate-900 dark:text-white sm:text-3xl"
            >
                {{ form.title }}
            </h1>
            <p
                v-if="form.description"
                class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-zinc-400"
            >
                {{ form.description }}
            </p>
        </div>

        <!-- Body: alasan kenapa tidak bisa diakses -->
        <div
            class="relative flex flex-col items-center overflow-hidden rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div
                class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-indigo-500/10 blur-3xl"
            ></div>

            <div
                class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-rose-500 text-white shadow-lg dark:text-white"
            >
                <component :is="config.icon" :size="26" stroke="1.5" />
            </div>

            <h2
                class="relative mt-4 text-xl font-bold text-slate-900 dark:text-white"
            >
                {{ config.title }}
            </h2>

            <p
                class="relative mt-1.5 max-w-sm text-sm leading-relaxed text-slate-500 dark:text-zinc-400"
            >
                {{ config.message }}
            </p>
        </div>
    </FormLayout>
</template>
