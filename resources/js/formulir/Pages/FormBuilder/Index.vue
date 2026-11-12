<!-- resources\js\Pages\FormBuilder\Index.vue -->
<script setup>
import { ref, computed } from "vue";
import FormulirLayout from "@/Layouts/FormulirLayout.vue";
import Button from "@/Components/Button.vue";
import StatusDot from "@/Components/StatusDot.vue";
import { Link, router } from "@inertiajs/vue3";

import {
    IconPlus,
    IconPencil,
    IconTrash,
    IconUsers,
    IconLink,
    IconFileText,
    IconArrowUpRight,
    IconSearch,
    IconLayoutList,
    IconLayoutGrid,
    IconX,
    IconChevronLeft,
    IconChevronRight,
    IconSchool,
} from "@tabler/icons-vue";

const props = defineProps({
    forms: Object,
});

/*
|--------------------------------------------------------------------------
| Default View = Card
|--------------------------------------------------------------------------
*/

const viewMode = ref("card");
const searchQuery = ref("");

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

const filteredForms = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.forms.data;
    }

    const q = searchQuery.value.toLowerCase().trim();

    return props.forms.data.filter(
        (form) =>
            form.title.toLowerCase().includes(q) ||
            form.slug.toLowerCase().includes(q),
    );
});

const hasSearch = computed(() => searchQuery.value.trim().length > 0);

const isEmpty = computed(() => props.forms.data.length === 0);

const isSearchEmpty = computed(
    () => hasSearch.value && filteredForms.value.length === 0,
);

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

function destroyForm(form) {
    if (
        confirm(
            `Hapus form "${form.title}"? Tindakan ini tidak bisa dibatalkan.`,
        )
    ) {
        router.delete(route("formulir.kelola.destroy", form.id));
    }
}

function clearSearch() {
    searchQuery.value = "";
}
</script>

<template>
    <FormulirLayout>
        <div class="mx-auto max-w-7xl">
            <!-- ===================================================== -->
            <!-- PAGE HERO -->
            <!-- ===================================================== -->

            <section
                class="relative mb-8 overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black/20"
            >
                <!-- Decorative glow -->
                <div
                    class="pointer-events-none absolute -right-24 -top-32 h-80 w-80 rounded-full bg-indigo-500/[0.10] blur-[80px] dark:bg-indigo-500/[0.12]"
                ></div>

                <div
                    class="pointer-events-none absolute -bottom-32 left-1/3 h-64 w-64 rounded-full bg-violet-500/[0.07] blur-[70px] dark:bg-violet-500/[0.08]"
                ></div>

                <div
                    class="relative flex flex-col gap-6 p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between"
                >
                    <!-- Heading -->
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-[0_10px_25px_rgba(79,70,229,0.25)]"
                        >
                            <IconFileText :size="26" stroke="1.6" />
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <span
                                    class="text-[11px] font-bold uppercase tracking-[0.16em] text-indigo-500 dark:text-indigo-400"
                                >
                                    Form Management
                                </span>

                                <span
                                    class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                                >
                                    {{ forms.total ?? 0 }}
                                </span>
                            </div>

                            <h1
                                class="font-display text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-[30px]"
                            >
                                Semua Form
                            </h1>

                            <p
                                class="mt-1.5 max-w-xl text-sm leading-relaxed text-slate-500 dark:text-zinc-400"
                            >
                                Kelola form pendataan sekolah, pantau pengisian,
                                dan atur formulir yang tersedia.
                            </p>
                        </div>
                    </div>

                    <!-- Create -->
                    <Link :href="route('formulir.kelola.create')" class="shrink-0">
                        <Button
                            variant="primary"
                            class="h-11 rounded-xl px-5 shadow-[0_8px_25px_rgba(79,70,229,0.25)]"
                        >
                            <template #icon>
                                <IconPlus :size="18" stroke="2" />
                            </template>

                            Buat Form Baru
                        </Button>
                    </Link>
                </div>
            </section>

            <!-- ===================================================== -->
            <!-- TOOLBAR -->
            <!-- ===================================================== -->

            <div
                v-if="!isEmpty"
                class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <!-- Search -->
                <div class="relative w-full sm:max-w-md">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4"
                    >
                        <IconSearch
                            :size="18"
                            stroke="1.7"
                            class="text-slate-400 dark:text-zinc-500"
                        />
                    </div>

                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari form..."
                        class="h-11 w-full rounded-xl border border-slate-300 bg-white pl-11 pr-10 text-sm font-medium text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/15 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-600 dark:focus:border-indigo-500"
                    />

                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="clearSearch"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-700 dark:text-zinc-500 dark:hover:text-white"
                    >
                        <IconX :size="16" stroke="2" />
                    </button>
                </div>

                <!-- Right -->
                <div
                    class="flex items-center justify-between gap-3 sm:justify-end"
                >
                    <p
                        class="text-xs font-medium text-slate-400 dark:text-zinc-500"
                    >
                        <span
                            class="font-bold text-slate-700 dark:text-zinc-200"
                        >
                            {{ filteredForms.length }}
                        </span>

                        {{ hasSearch ? "hasil" : "form" }}
                    </p>

                    <!-- View switcher -->
                    <div
                        class="flex items-center rounded-xl border border-slate-300 bg-white p-1 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <button
                            type="button"
                            @click="viewMode = 'card'"
                            class="flex h-8 w-9 items-center justify-center rounded-lg transition-all"
                            :class="
                                viewMode === 'card'
                                    ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900'
                                    : 'text-slate-400 hover:text-slate-700 dark:text-zinc-500 dark:hover:text-white'
                            "
                            title="Tampilan Card"
                        >
                            <IconLayoutGrid :size="17" stroke="1.8" />
                        </button>

                        <button
                            type="button"
                            @click="viewMode = 'list'"
                            class="flex h-8 w-9 items-center justify-center rounded-lg transition-all"
                            :class="
                                viewMode === 'list'
                                    ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900'
                                    : 'text-slate-400 hover:text-slate-700 dark:text-zinc-500 dark:hover:text-white'
                            "
                            title="Tampilan List"
                        >
                            <IconLayoutList :size="17" stroke="1.8" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- EMPTY -->
            <!-- ===================================================== -->

            <div
                v-if="isEmpty"
                class="relative overflow-hidden rounded-[28px] border border-dashed border-slate-300 bg-white px-6 py-20 shadow-card text-center dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="pointer-events-none absolute left-1/2 top-0 h-56 w-56 -translate-x-1/2 rounded-full bg-indigo-500/10 blur-[70px]"
                ></div>

                <div class="relative mx-auto max-w-md">
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500/10 to-violet-500/10 text-indigo-600 dark:text-indigo-400"
                    >
                        <IconFileText :size="30" stroke="1.5" />
                    </div>

                    <h3
                        class="mt-6 text-xl font-bold text-slate-900 dark:text-white"
                    >
                        Belum ada form
                    </h3>

                    <p
                        class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-zinc-400"
                    >
                        Buat form pertama untuk mulai mengumpulkan data dari
                        sekolah.
                    </p>

                    <Link
                        :href="route('formulir.kelola.create')"
                        class="mt-7 inline-block"
                    >
                        <Button variant="primary">
                            <template #icon>
                                <IconPlus :size="18" stroke="2" />
                            </template>

                            Buat Form Pertama
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- SEARCH EMPTY -->
            <!-- ===================================================== -->

            <div
                v-else-if="isSearchEmpty"
                class="rounded-[28px] border border-slate-200 bg-white px-6 py-16 text-center shadow-card ring-1 ring-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-200/70 text-slate-500 dark:bg-zinc-800 dark:text-zinc-500"
                >
                    <IconSearch :size="25" stroke="1.6" />
                </div>

                <h3
                    class="mt-5 text-lg font-bold text-slate-900 dark:text-white"
                >
                    Tidak ada hasil
                </h3>

                <p class="mt-1.5 text-sm text-slate-500 dark:text-zinc-400">
                    Tidak ditemukan form dengan kata kunci
                    <span
                        class="font-semibold text-slate-700 dark:text-zinc-200"
                    >
                        "{{ searchQuery }}"
                    </span>
                </p>

                <button
                    type="button"
                    @click="clearSearch"
                    class="mt-5 text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                >
                    Hapus pencarian
                </button>
            </div>

            <!-- ===================================================== -->
            <!-- CARD VIEW — DEFAULT -->
            <!-- ===================================================== -->

            <div
                v-else-if="viewMode === 'card'"
                class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="form in filteredForms"
                    :key="form.id"
                    class="group relative flex min-h-[255px] flex-col overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-card ring-1 ring-slate-900/5 transition-all duration-300 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-lift dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-indigo-500/30 dark:hover:shadow-black/30"
                >
                    <!-- Top accent -->
                    <div
                        class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-violet-500 to-indigo-400 opacity-90 transition-opacity group-hover:opacity-100"
                    ></div>

                    <!-- Decorative glow -->
                    <div
                        class="pointer-events-none absolute -right-16 -top-16 h-36 w-36 rounded-full bg-indigo-500/[0.06] blur-3xl transition-all duration-300 group-hover:bg-indigo-500/[0.10]"
                    ></div>

                    <!-- Header -->
                    <div
                        class="relative flex items-start justify-between gap-3 p-5 pb-3"
                    >
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-50 text-indigo-600 ring-1 ring-indigo-500/5 dark:from-indigo-500/10 dark:to-violet-500/10 dark:text-indigo-400 dark:ring-indigo-400/10"
                        >
                            <IconFileText :size="22" stroke="1.7" />
                        </div>

                        <!-- Actions -->
                        <!-- Card View — ganti bagian "Actions" -->
                        <div
                            class="flex items-center gap-1 opacity-100 transition-opacity sm:opacity-0 sm:group-hover:opacity-100"
                        >
                            <Link
                                :href="route('formulir.kelola.edit', form.id)"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-800 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-white"
                                title="Edit"
                            >
                                <IconPencil :size="16" stroke="1.8" />
                            </Link>

                            <button
                                type="button"
                                @click="destroyForm(form)"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:text-zinc-500 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                title="Hapus"
                            >
                                <IconTrash :size="16" stroke="1.8" />
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="relative flex flex-1 flex-col px-5">
                        <Link
                            :href="route('formulir.kelola.edit', form.id)"
                            class="line-clamp-2 pr-4 text-[16px] font-bold leading-snug tracking-[-0.01em] text-slate-900 transition-colors hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                        >
                            {{ form.title }}
                        </Link>

                        <!-- Status -->
                        <div class="mt-3">
                            <StatusDot :status="form.status" />
                        </div>

                        <!-- Slug -->
                        <div
                            class="mt-4 flex min-w-0 items-center gap-2 text-xs font-medium text-slate-500 dark:text-zinc-500"
                        >
                            <IconLink
                                :size="14"
                                stroke="1.7"
                                class="shrink-0"
                            />

                            <span class="truncate font-mono">
                                /form/{{ form.slug }}
                            </span>
                        </div>

                        <!-- Sekolah tujuan -->
                        <div
                            class="mt-2 flex min-w-0 items-center gap-2 text-xs font-medium text-slate-500 dark:text-zinc-500"
                        >
                            <IconSchool
                                :size="14"
                                stroke="1.7"
                                class="shrink-0"
                            />

                            <span class="truncate">
                                {{ form.sekolah_nama ?? "Semua Sekolah" }}
                            </span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="relative mt-5 flex items-center justify-between border-t border-slate-200/80 bg-slate-50 px-5 py-3.5 dark:border-zinc-800 dark:bg-zinc-950/30"
                    >
                        <div
                            class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-zinc-400"
                        >
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-indigo-500 shadow-sm dark:bg-zinc-800 dark:text-indigo-400"
                            >
                                <IconUsers :size="14" stroke="1.8" />
                            </div>
                            <span>
                                <strong
                                    class="text-slate-800 dark:text-zinc-200"
                                    >{{ form.submissions_count ?? 0 }}</strong
                                >
                                pengisian
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <Link
                                :href="route('formulir.kelola.edit', form.id)"
                                class="flex items-center gap-1 text-xs font-bold text-indigo-600 transition hover:gap-1.5 dark:text-indigo-400"
                            >
                                Kelola
                                <IconArrowUpRight :size="14" stroke="2" />
                            </Link>
                        </div>
                    </div>
                </article>
            </div>

            <!-- ===================================================== -->
            <!-- LIST VIEW -->
            <!-- ===================================================== -->

            <div v-else class="space-y-3">
                <div
                    v-for="form in filteredForms"
                    :key="form.id"
                    class="group flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-card ring-1 ring-slate-900/5 transition hover:-translate-y-[1px] hover:border-indigo-300 hover:shadow-lift dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-indigo-500/30"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            <IconFileText :size="20" stroke="1.7" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <Link
                                :href="route('formulir.kelola.edit', form.id)"
                                class="flex items-center gap-1.5 truncate text-sm font-bold text-slate-800 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                            >
                                {{ form.title }}

                                <IconArrowUpRight
                                    :size="14"
                                    stroke="2"
                                    class="shrink-0 opacity-0 transition group-hover:opacity-100"
                                />
                            </Link>

                            <div
                                class="mt-2 flex flex-wrap items-center gap-4 text-xs font-medium text-slate-500 dark:text-zinc-500"
                            >
                                <StatusDot :status="form.status" />

                                <span class="flex items-center gap-1.5">
                                    <IconUsers :size="14" stroke="1.7" />

                                    {{ form.submissions_count ?? 0 }}
                                    pengisian
                                </span>

                                <span class="flex items-center gap-1.5">
                                    <IconSchool :size="14" stroke="1.7" />

                                    {{
                                        form.sekolah_nama ?? "Semua Sekolah"
                                    }}
                                </span>

                                <span
                                    class="hidden items-center gap-1.5 font-mono sm:flex"
                                >
                                    <IconLink :size="13" stroke="1.7" />

                                    /form/{{ form.slug }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- List View -->
                    <div
                        class="flex shrink-0 items-center gap-1 opacity-100 sm:opacity-0 sm:group-hover:opacity-100"
                    >
                        <Link
                            :href="route('formulir.kelola.edit', form.id)"
                            class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-800 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-white"
                            title="Edit Jawaban"
                        >
                            <IconPencil :size="17" stroke="1.8" />
                        </Link>

                        <button
                            type="button"
                            @click="destroyForm(form)"
                            title="Hapus Form"
                            class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 dark:text-zinc-500 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                        >
                            <IconTrash :size="17" stroke="1.8" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- PAGINATION -->
            <!-- ===================================================== -->

            <div
                v-if="
                    !isEmpty &&
                    !isSearchEmpty &&
                    forms.links &&
                    forms.links.length > 3
                "
                class="mt-8 flex flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <p
                    class="text-xs font-medium text-slate-400 dark:text-zinc-500"
                >
                    Menampilkan
                    <span class="font-bold text-slate-700 dark:text-zinc-200">
                        {{ forms.from ?? 0 }}
                    </span>
                    –
                    <span class="font-bold text-slate-700 dark:text-zinc-200">
                        {{ forms.to ?? 0 }}
                    </span>
                    dari
                    <span class="font-bold text-slate-700 dark:text-zinc-200">
                        {{ forms.total ?? 0 }}
                    </span>
                    form
                </p>

                <div class="flex items-center gap-1">
                    <template v-for="(link, index) in forms.links" :key="index">
                        <!-- Previous -->
                        <Link
                            v-if="
                                link.label.includes('Previous') ||
                                link.label.includes('&laquo;')
                            "
                            :href="link.url || '#'"
                            preserve-scroll
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-indigo-200 hover:text-indigo-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:border-indigo-500/30 dark:hover:text-indigo-400"
                            :class="{
                                'pointer-events-none opacity-40': !link.url,
                            }"
                        >
                            <IconChevronLeft :size="17" stroke="1.8" />
                        </Link>

                        <!-- Next -->
                        <Link
                            v-else-if="
                                link.label.includes('Next') ||
                                link.label.includes('&raquo;')
                            "
                            :href="link.url || '#'"
                            preserve-scroll
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-indigo-200 hover:text-indigo-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:border-indigo-500/30 dark:hover:text-indigo-400"
                            :class="{
                                'pointer-events-none opacity-40': !link.url,
                            }"
                        >
                            <IconChevronRight :size="17" stroke="1.8" />
                        </Link>

                        <!-- Pages -->
                        <Link
                            v-else
                            :href="link.url || '#'"
                            preserve-scroll
                            v-html="link.label"
                            class="flex h-9 min-w-[36px] items-center justify-center rounded-lg px-2.5 text-xs font-bold transition"
                            :class="
                                link.active
                                    ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900'
                                    : 'text-slate-500 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white'
                            "
                        />
                    </template>
                </div>
            </div>
        </div>
    </FormulirLayout>
</template>
