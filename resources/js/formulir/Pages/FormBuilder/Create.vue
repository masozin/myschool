<!-- resources/js/Pages/FormBuilder/Create.vue -->
<script setup>
import FormulirLayout from "@/Layouts/FormulirLayout.vue";
import Card from "@/Components/Card.vue";
import Button from "@/Components/Button.vue";
import TextInput from "@/Components/TextInput.vue";
import { Link, useForm } from "@inertiajs/vue3";
import {
    IconPhoto,
    IconArrowLeft,
    IconArrowRight,
    IconFilePlus,
} from "@tabler/icons-vue";
import { ref } from "vue";

defineProps({
    sekolahList: { type: Array, default: () => [] },
});

const form = useForm({
    title: "",
    description: "",
    status: "draft",
    start_at: "",
    end_at: "",
    allow_multiple_submissions: false,
    require_login: true,
    target_role: "operator",
    school_id: null,
    banner_image: null,
});

const bannerPreview = ref(null);

function onBannerChange(e) {
    const file = e.target.files[0];
    form.banner_image = file;
    bannerPreview.value = file ? URL.createObjectURL(file) : null;
}

function submit() {
    form.post(route("formulir.kelola.store"), { forceFormData: true });
}
</script>

<template>
    <FormulirLayout>
        <template #header>
            <div class="flex items-center gap-2 text-sm">
                <Link
                    :href="route('formulir.kelola.index')"
                    class="text-slate-500 transition hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white"
                >
                    Form
                </Link>
                <span class="text-slate-300 dark:text-zinc-700">/</span>
                <span class="font-semibold text-slate-900 dark:text-white">
                    Buat Baru
                </span>
            </div>
        </template>

        <div class="mx-auto max-w-2xl">
            <!-- Header -->
            <div class="mb-6 flex items-start gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-600 text-white shadow-[0_8px_25px_rgba(79,70,229,0.28)]"
                >
                    <IconFilePlus :size="22" stroke="1.75" />
                </div>
                <div>
                    <h1
                        class="text-2xl font-bold tracking-[-0.01em] text-slate-900 dark:text-white"
                    >
                        Buat Form Baru
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
                        Lengkapi info dasar dulu — kamu bisa menambahkan
                        pertanyaan setelah ini disimpan.
                    </p>
                </div>
            </div>

            <Card :hover="false">
                <form @submit.prevent="submit" class="space-y-6">
                    <TextInput
                        v-model="form.title"
                        label="Judul Form"
                        placeholder="Contoh: Pendaftaran Siswa Baru 2026"
                        :error="form.errors.title"
                    />

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-900 dark:text-white"
                        >
                            Deskripsi
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Jelaskan singkat tujuan form ini"
                            class="w-full rounded-md border-slate-200 bg-white text-sm text-slate-900 placeholder:text-slate-400 transition-colors duration-150 focus:border-indigo-500 focus:ring-indigo-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500"
                        ></textarea>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-900 dark:text-white"
                        >
                            Banner
                        </label>
                        <label
                            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-200 bg-slate-50 py-8 text-center transition-colors hover:bg-slate-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
                            :style="
                                bannerPreview
                                    ? {
                                          backgroundImage: `url(${bannerPreview})`,
                                          backgroundSize: 'cover',
                                          backgroundPosition: 'center',
                                      }
                                    : {}
                            "
                        >
                            <template v-if="!bannerPreview">
                                <IconPhoto
                                    :size="24"
                                    stroke="1.5"
                                    class="text-slate-400 dark:text-zinc-500"
                                />
                                <span
                                    class="text-sm text-slate-500 dark:text-zinc-400"
                                >
                                    Klik untuk unggah gambar banner (opsional)
                                </span>
                            </template>
                            <span
                                v-else
                                class="rounded-md bg-slate-950/60 px-3 py-1 text-xs text-white backdrop-blur-sm"
                            >
                                Ganti gambar
                            </span>
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="onBannerChange"
                            />
                        </label>
                        <p
                            v-if="form.errors.banner_image"
                            class="mt-1.5 text-sm text-red-500 dark:text-red-400"
                        >
                            {{ form.errors.banner_image }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-900 dark:text-white"
                        >
                            Status
                        </label>
                        <select
                            v-model="form.status"
                            class="w-full rounded-md border-slate-200 bg-white text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        >
                            <option value="draft">Draft</option>
                            <option value="published">Terbit</option>
                            <option value="closed">Ditutup</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <TextInput
                            v-model="form.start_at"
                            type="datetime-local"
                            label="Mulai"
                        />
                        <TextInput
                            v-model="form.end_at"
                            type="datetime-local"
                            label="Selesai"
                            :error="form.errors.end_at"
                        />
                    </div>

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition-colors hover:bg-slate-50 dark:border-zinc-800 dark:hover:bg-zinc-900"
                    >
                        <input
                            v-model="form.allow_multiple_submissions"
                            type="checkbox"
                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-800"
                        />
                        <div>
                            <p
                                class="text-sm font-medium text-slate-900 dark:text-white"
                            >
                                Izinkan pengisian berulang
                            </p>
                            <p
                                class="text-sm text-slate-500 dark:text-zinc-400"
                            >
                                Satu orang dapat mengisi form ini lebih dari
                                satu kali.
                            </p>
                        </div>
                    </label>

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition-colors hover:bg-slate-50 dark:border-zinc-800 dark:hover:bg-zinc-900"
                    >
                        <input
                            v-model="form.require_login"
                            type="checkbox"
                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-800"
                        />
                        <div>
                            <p
                                class="text-sm font-medium text-slate-900 dark:text-white"
                            >
                                Wajib login untuk mengisi
                            </p>
                            <p
                                class="text-sm text-slate-500 dark:text-zinc-400"
                            >
                                Pengisi harus masuk ke akun terlebih dahulu
                                sebelum bisa mengirim form ini.
                            </p>
                        </div>
                    </label>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-900 dark:text-white"
                        >
                            Ditujukan untuk
                        </label>
                        <select
                            v-model="form.target_role"
                            class="w-full rounded-md border-slate-200 bg-white text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
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
                        <p
                            v-if="form.errors.target_role"
                            class="mt-1.5 text-sm text-red-500 dark:text-red-400"
                        >
                            {{ form.errors.target_role }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-900 dark:text-white"
                        >
                            Sekolah Tujuan
                        </label>
                        <select
                            v-model="form.school_id"
                            class="w-full rounded-md border-slate-200 bg-white text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
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
                        <p
                            v-if="form.errors.school_id"
                            class="mt-1.5 text-sm text-red-500 dark:text-red-400"
                        >
                            {{ form.errors.school_id }}
                        </p>
                    </div>

                    <div
                        class="flex items-center justify-between border-t border-slate-200 pt-6 dark:border-zinc-800"
                    >
                        <Link :href="route('formulir.kelola.index')">
                            <Button variant="ghost">
                                <template #icon>
                                    <IconArrowLeft :size="17" stroke="1.75" />
                                </template>
                                Batal
                            </Button>
                        </Link>
                        <Button
                            type="submit"
                            variant="primary"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? "Menyimpan..."
                                    : "Lanjut Tambah Pertanyaan"
                            }}
                            <template #icon>
                                <IconArrowRight :size="17" stroke="1.75" />
                            </template>
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    </FormulirLayout>
</template>
