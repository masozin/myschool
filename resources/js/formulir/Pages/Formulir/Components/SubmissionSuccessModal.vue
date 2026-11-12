<!-- resources/js/Pages/Form/Components/SubmissionSuccessModal.vue -->
<script setup>
import { ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";
import {
    IconCircleCheck,
    IconEye,
    IconListDetails,
    IconCopy,
    IconLink,
    IconX,
} from "@tabler/icons-vue";

const props = defineProps({
    payload: Object, // { is_update, submission_token, review_url?, list_url?, is_guest_link? }
});

const open = ref(false);
const copied = ref(false);

watch(
    () => props.payload,
    (val) => {
        if (val) open.value = true;
    },
    { immediate: true },
);

function copyLink() {
    navigator.clipboard.writeText(props.payload.review_url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
}

function close() {
    open.value = false;
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4"
                @click.self="close"
            >
                <Transition
                    appear
                    enter-active-class="transition-all duration-250 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                >
                    <div
                        class="relative w-full max-w-md overflow-hidden rounded-xl bg-white p-6 shadow-hover"
                    >
                        <div
                            class="absolute -right-14 -top-14 h-40 w-40 rounded-full bg-brand/10 blur-3xl"
                        ></div>

                        <button
                            @click="close"
                            class="absolute right-4 top-4 text-muted hover:text-ink"
                        >
                            <IconX :size="18" />
                        </button>

                        <div
                            class="relative flex h-14 w-14 items-center justify-center rounded-full bg-brand/10 text-brand"
                        >
                            <IconCircleCheck :size="28" stroke="1.5" />
                        </div>

                        <h2 class="relative mt-4 font-display text-xl text-ink">
                            {{
                                payload.is_update
                                    ? "Perubahan tersimpan"
                                    : "Jawaban berhasil dikirim"
                            }}
                        </h2>
                        <p class="relative mt-1 text-sm text-muted">
                            {{
                                payload.is_update
                                    ? "Jawaban kamu sudah diperbarui."
                                    : "Terima kasih sudah mengisi form ini."
                            }}
                        </p>

                        <!-- Guest tanpa login: tampilkan link edit pribadi -->
                        <div
                            v-if="payload.is_guest_link"
                            class="relative mt-4 rounded-lg border border-border dark:border-border-dark/60 bg-canvas dark:bg-zinc-900/50 p-3"
                        >
                            <p
                                class="mb-2 flex items-center gap-1.5 text-xs font-medium text-ink"
                            >
                                <IconLink :size="14" /> Simpan link ini untuk
                                edit jawabanmu nanti
                            </p>
                            <div class="flex items-center gap-2">
                                <input
                                    readonly
                                    :value="payload.review_url"
                                    class="flex-1 truncate rounded-md border-border dark:border-border-dark bg-white text-xs text-muted"
                                    @click="$event.target.select()"
                                />
                                <button
                                    @click="copyLink"
                                    class="shrink-0 rounded-md border border-border dark:border-border-dark/60 bg-white p-2 text-muted hover:text-brand"
                                >
                                    <IconCopy :size="15" />
                                </button>
                            </div>
                            <p
                                v-if="copied"
                                class="mt-1.5 text-xs text-brand"
                            >
                                Tersalin ke clipboard
                            </p>
                            <p class="mt-2 text-xs text-muted">
                                Link ini berlaku 90 hari. Karena kamu tidak
                                login, ini satu-satunya cara untuk kembali
                                mengedit jawabanmu.
                            </p>
                        </div>

                        <div class="relative mt-5 flex flex-col gap-2">
                            <Link
                                v-if="payload.review_url"
                                :href="payload.review_url"
                            >
                                <button
                                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-medium text-canvas hover:bg-brand-dark transition-colors"
                                >
                                    <IconEye :size="16" /> Lihat Jawaban
                                </button>
                            </Link>
                            <Link
                                v-if="payload.list_url"
                                :href="payload.list_url"
                            >
                                <button
                                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-medium text-canvas hover:bg-brand-dark transition-colors"
                                >
                                    <IconListDetails :size="16" /> Lihat Daftar
                                    Jawaban Anda
                                </button>
                            </Link>
                            <button
                                @click="close"
                                class="w-full rounded-lg border border-border dark:border-border-dark/60 px-4 py-2.5 text-sm font-medium text-ink hover:bg-canvas dark:bg-zinc-900 transition-colors"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
