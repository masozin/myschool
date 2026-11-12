<!-- resources/js/Pages/FormBuilder/Components/FormMetaOffcanvas.vue -->
<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import TextInput from "@/Components/TextInput.vue";
import Button from "@/Components/Button.vue";
import { IconX, IconPhoto } from "@tabler/icons-vue";
import ImageCropperModal from "@/Components/ImageCropperModal.vue";

const props = defineProps({
    open: Boolean,
    form: Object,
    sekolahList: { type: Array, default: () => [] },
});
const emit = defineEmits(["close"]);

const local = ref({});
const bannerFile = ref(null);
const bannerPreview = ref(null);
const processing = ref(false);

watch(
    () => props.open,
    (val) => {
        if (val) {
            local.value = {
                title: props.form.title,
                description: props.form.description,
                status: props.form.status,
                start_at: props.form.start_at?.slice(0, 16) ?? "",
                end_at: props.form.end_at?.slice(0, 16) ?? "",
                allow_multiple_submissions:
                    props.form.allow_multiple_submissions,
                require_login: props.form.require_login ?? true,
                target_role: props.form.target_role ?? "operator",
                school_id: props.form.sekolah_id ?? null,
            };
            bannerFile.value = null;
            bannerPreview.value = props.form.banner_image
                ? `/storage/${props.form.banner_image}`
                : null;
        }
    },
);

function submit() {
    processing.value = true;
    router.post(
        route("formulir.kelola.update", props.form.id),
        {
            _method: "put",
            ...local.value,
            banner_image: bannerFile.value,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => emit("close"),
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

// state crop
const cropperOpen = ref(false);
const rawImageSrc = ref(null);

function onBannerChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    rawImageSrc.value = URL.createObjectURL(file);
    cropperOpen.value = true;
    e.target.value = ""; // reset input supaya bisa pilih file sama lagi kalau batal
}

function onCropped(croppedFile) {
    bannerFile.value = croppedFile;
    bannerPreview.value = URL.createObjectURL(croppedFile);
    cropperOpen.value = false;
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
                class="fixed inset-0 z-50 bg-slate-950/40"
                @click="$emit('close')"
            ></div>
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-250 ease-out"
            leave-active-class="transition-transform duration-200 ease-in"
            enter-from-class="translate-x-full"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="open"
                class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl dark:bg-zinc-950"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-zinc-800"
                >
                    <h3
                        class="text-lg font-bold text-slate-900 dark:text-white"
                    >
                        Edit Info Form
                    </h3>
                    <button
                        @click="$emit('close')"
                        class="text-slate-400 hover:text-slate-900 dark:text-zinc-500 dark:hover:text-white"
                    >
                        <IconX :size="20" />
                    </button>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5">
                    <TextInput v-model="local.title" label="Judul Form" />

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-zinc-200"
                        >
                            Deskripsi
                        </label>
                        <textarea
                            v-model="local.description"
                            rows="3"
                            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-zinc-200"
                        >
                            Banner
                        </label>
                        <label
                            class="flex h-32 cursor-pointer items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 bg-cover bg-center text-sm font-medium text-slate-600 hover:border-indigo-400 hover:bg-indigo-50/60 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800"
                            :style="
                                bannerPreview
                                    ? {
                                          backgroundImage: `url(${bannerPreview})`,
                                      }
                                    : {}
                            "
                        >
                            <span
                                v-if="!bannerPreview"
                                class="flex items-center gap-2"
                            >
                                <IconPhoto :size="18" /> Unggah banner
                            </span>
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="onBannerChange"
                            />

                            <ImageCropperModal
                                :open="cropperOpen"
                                :image-src="rawImageSrc"
                                :aspect-ratio="16 / 9"
                                @close="cropperOpen = false"
                                @cropped="onCropped"
                            />
                        </label>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-zinc-200"
                        >
                            Status
                        </label>
                        <select
                            v-model="local.status"
                            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        >
                            <option value="draft">Draft</option>
                            <option value="published">Terbit</option>
                            <option value="closed">Ditutup</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <TextInput
                            v-model="local.start_at"
                            type="datetime-local"
                            label="Mulai"
                        />
                        <TextInput
                            v-model="local.end_at"
                            type="datetime-local"
                            label="Selesai"
                        />
                    </div>

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 bg-white p-3 shadow-sm transition-all hover:border-indigo-300 dark:border-zinc-800"
                    >
                        <input
                            v-model="local.allow_multiple_submissions"
                            type="checkbox"
                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-800"
                        />
                        <div>
                            <p
                                class="text-sm font-medium text-slate-900 dark:text-white"
                            >
                                Izinkan pengisian berulang
                            </p>
                        </div>
                    </label>

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 bg-white p-3 shadow-sm transition-all hover:border-indigo-300 dark:border-zinc-800"
                    >
                        <input
                            v-model="local.require_login"
                            type="checkbox"
                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-800"
                        />
                        <div>
                            <p
                                class="text-sm font-medium text-slate-900 dark:text-white"
                            >
                                Wajib login untuk mengisi
                            </p>
                        </div>
                    </label>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-zinc-200"
                        >
                            Ditujukan untuk
                        </label>
                        <select
                            v-model="local.target_role"
                            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        >
                            <option value="semua">Semua</option>
                            <option value="operator">Operator</option>
                            <option value="guru">Guru (akan datang)</option>
                            <option value="kepala_sekolah">
                                Kepala Sekolah (akan datang)
                            </option>
                            <option value="murid">Murid (akan datang)</option>
                        </select>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-zinc-400"
                        >
                            Menentukan siapa yang bisa melihat & mengisi form
                            ini.
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-zinc-200"
                        >
                            Sekolah Tujuan
                        </label>
                        <select
                            v-model="local.school_id"
                            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm text-slate-900 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        >
                            <option :value="null">Semua Sekolah</option>
                            <option
                                v-for="sekolah in sekolahList"
                                :key="sekolah.id"
                                :value="sekolah.id"
                            >
                                {{ sekolah.nama }}
                            </option>
                        </select>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-zinc-400"
                        >
                            Batasi form ini untuk satu sekolah, atau biarkan
                            terbuka untuk semua sekolah.
                        </p>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-zinc-800"
                >
                    <Button variant="ghost" @click="$emit('close')">
                        Batal
                    </Button>
                    <Button
                        variant="primary"
                        :disabled="processing"
                        @click="submit"
                    >
                        {{ processing ? "Menyimpan..." : "Simpan" }}
                    </Button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
