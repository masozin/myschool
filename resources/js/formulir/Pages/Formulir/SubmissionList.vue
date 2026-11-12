<!-- resources/js/Pages/Form/SubmissionList.vue -->
<script setup>
import { usePage } from "@inertiajs/vue3";
import SubmissionSuccessModal from "./Components/SubmissionSuccessModal.vue";
import FormLayout from "@/Layouts/FormLayout.vue";
import { Link } from "@inertiajs/vue3";
import { computed } from 'vue';
import {
    IconPlus,
    IconPencil,
    IconCalendarEvent,
    IconInbox,
} from "@tabler/icons-vue";

const page = usePage();
const successPayload = computed(
    () => page.props.flash?.success_submission ?? null,
);

const props = defineProps({
    form: Object,
    submissions: Array,
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
</script>

<template>
    <FormLayout :end-at="form.end_at">
        <template #banner>
            <div
                v-if="form.banner_image"
                class="relative h-40 w-full overflow-hidden sm:h-52"
            >
                <img
                    :src="`/storage/${form.banner_image}`"
                    class="h-full w-full object-cover"
                    :alt="form.title"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-ink/60 via-ink/10 to-transparent"
                ></div>
            </div>
        </template>

        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink">
                    {{ form.title }}
                </h1>
                <p v-if="form.description" class="mt-1.5 text-sm text-muted">
                    {{ form.description }}
                </p>
            </div>
        </div>

        <Link :href="route('formulir.publik.isi', form.slug)">
            <button
                class="mb-6 flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-brand/40 bg-brand/5 py-4 text-sm font-medium text-brand transition-colors hover:border-brand hover:bg-brand/10"
            >
                <IconPlus :size="18" stroke="1.75" /> Tambah Jawaban Baru
            </button>
        </Link>

        <div
            v-if="submissions.length === 0"
            class="flex flex-col items-center rounded-xl border border-dashed border-border dark:border-border-dark bg-canvas dark:bg-zinc-900/40 px-6 py-14 text-center"
        >
            <IconInbox :size="28" stroke="1.5" class="text-muted" />
            <p class="mt-3 text-sm text-muted">
                Kamu belum mengirim jawaban apa pun untuk form ini.
            </p>
        </div>

        <div v-else class="space-y-3">
            <p class="text-sm font-medium text-ink">
                Riwayat Jawaban ({{ submissions.length }})
            </p>

            <div
                v-for="(s, i) in submissions"
                :key="s.id"
                class="flex items-center justify-between rounded-xl border border-border dark:border-border-dark/60 bg-white p-4"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-canvas dark:bg-zinc-900 text-sm font-medium text-ink"
                    >
                        {{ submissions.length - i }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-ink">
                            Jawaban #{{ submissions.length - i }}
                        </p>
                        <p class="flex items-center gap-1 text-xs text-muted">
                            <IconCalendarEvent :size="13" />
                            {{ formatDate(s.submitted_at) }}
                        </p>
                    </div>
                </div>

                <Link
                    :href="route('formulir.publik.edit', [form.slug, s.id])"
                    class="flex items-center gap-1.5 rounded-md border border-border dark:border-border-dark/70 px-3 py-1.5 text-sm font-medium text-ink hover:bg-canvas dark:bg-zinc-900 transition-colors"
                >
                    <IconPencil :size="15" /> Edit
                </Link>
            </div>
        </div>
    </FormLayout>
    <SubmissionSuccessModal :payload="successPayload" />
</template>
