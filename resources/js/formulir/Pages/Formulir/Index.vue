<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
    sekolah: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Formulir K3S" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
            <header class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
                    MySchool
                </p>
                <h1 class="mt-1 text-3xl font-bold">Formulir K3S</h1>
                <p class="mt-2 text-slate-600">
                    Modul formulir ini sekarang memakai akun myschool yang sama —
                    tidak ada login terpisah.
                </p>
            </header>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold">Kamu masuk sebagai</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex flex-wrap gap-x-3">
                        <dt class="w-28 shrink-0 font-medium text-slate-500">Nama</dt>
                        <dd class="font-medium">{{ props.user.name }}</dd>
                    </div>
                    <div class="flex flex-wrap gap-x-3">
                        <dt class="w-28 shrink-0 font-medium text-slate-500">Username</dt>
                        <dd>{{ props.user.user_name }}</dd>
                    </div>
                    <div class="flex flex-wrap gap-x-3">
                        <dt class="w-28 shrink-0 font-medium text-slate-500">Role</dt>
                        <dd>
                            <span
                                v-for="role in props.user.roles"
                                :key="role"
                                class="mr-1 inline-block rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700"
                            >
                                {{ role }}
                            </span>
                            <span v-if="props.user.roles.length === 0" class="text-slate-400">
                                (belum ada role)
                            </span>
                        </dd>
                    </div>
                    <div v-if="props.sekolah" class="flex flex-wrap gap-x-3">
                        <dt class="w-28 shrink-0 font-medium text-slate-500">Sekolah</dt>
                        <dd>
                            {{ props.sekolah.nama ?? 'Sekolah' }}
                            <span class="text-slate-400">
                                — NPSN {{ props.sekolah.npsn }} · {{ props.sekolah.jenjang }}
                            </span>
                        </dd>
                    </div>
                </dl>

                <p v-if="props.isAdmin" class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    Kamu adalah Admin — pengelola formulir.
                </p>
            </section>

            <section class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white/60 p-6 text-sm text-slate-600">
                Fondasi integrasi selesai. Halaman pembuat formulir (builder) dan
                halaman pengisian akan tiba di batch berikutnya.
            </section>
        </div>
    </div>
</template>
