<!-- resources/js/Pages/FormBuilder/Edit.vue -->
<script setup>
import { ref } from "vue";
import draggable from "vuedraggable";
import FormulirLayout from "@/Layouts/FormulirLayout.vue";
import Button from "@/Components/Button.vue";
import FormHeaderCard from "./Components/FormHeaderCard.vue";
import FormMetaOffcanvas from "./Components/FormMetaOffcanvas.vue";
import SectionHeader from "./Components/SectionHeader.vue";
import FieldCard from "./Components/FieldCard.vue";
import TypeAndSectionPicker from "./Components/TypeAndSectionPicker.vue";
import { useFormBuilder } from "@/Composables/useFormBuilder";
import { Link } from "@inertiajs/vue3";
import {
    IconDeviceFloppy,
    IconCheck,
    IconPlus,
    IconLayoutRows,
    IconArrowLeft,
    IconEye,
} from "@tabler/icons-vue";

const props = defineProps({
    form: Object,
    sekolahList: { type: Array, default: () => [] },
});

const {
    state,
    renderItems,
    totalFieldCount,
    insertFieldAt,
    insertSectionAt,
    removeItem,
    duplicateField,
    moveItem,
    save,
} = useFormBuilder(props.form);

const metaOpen = ref(false);
const pickerIndex = ref(null);

function openPickerAt(index) {
    pickerIndex.value = index;
}

function closePicker() {
    pickerIndex.value = null;
}

function pickField(type) {
    const idx = pickerIndex.value;
    closePicker();
    insertFieldAt(idx, type);
}

function pickSection() {
    const idx = pickerIndex.value;
    closePicker();
    insertSectionAt(idx);
}

function isFirst(index) {
    return index === 0;
}

function isLast(index) {
    return index === state.items.length - 1;
}
</script>

<template>
    <FormulirLayout>
        <template #header>
            <div class="flex min-w-0 items-center gap-2 text-sm">
                <Link
                    :href="route('formulir.kelola.index')"
                    class="inline-flex shrink-0 items-center gap-1.5 text-slate-500 transition hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white"
                >
                    <IconArrowLeft :size="16" />
                    <span class="hidden sm:inline">Form</span>
                </Link>

                <span class="text-slate-300 dark:text-zinc-700">/</span>

                <span
                    class="truncate font-semibold text-slate-900 dark:text-white"
                    :title="form.title"
                >
                    {{ form.title }}
                </span>
            </div>
        </template>

        <div class="pb-28">
            <div class="mx-auto max-w-4xl">
                <section class="min-w-0">
                    <FormHeaderCard
                        :form="form"
                        :field-count="totalFieldCount"
                        @edit="metaOpen = true"
                    />

                    <button
                        type="button"
                        @click="openPickerAt(0)"
                        class="group mt-5 flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-indigo-300 bg-white px-4 py-4 text-sm font-semibold text-slate-600 shadow-card transition-all hover:border-indigo-500 hover:bg-indigo-50/60 hover:text-indigo-700 hover:shadow-lift dark:border-indigo-500/30 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:border-indigo-400/60 dark:hover:bg-zinc-800 dark:hover:text-indigo-400"
                    >
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition-transform group-hover:scale-105 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            <IconPlus :size="18" stroke="2" />
                        </span>
                        <span>Tambah pertanyaan atau bagian</span>
                    </button>

                    <div class="relative mt-5">
                        <div
                            v-if="state.items.length === 0"
                            class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center shadow-card dark:border-zinc-800 dark:bg-zinc-900"
                        >
                            <div
                                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                            >
                                <IconLayoutRows :size="27" stroke="1.7" />
                            </div>

                            <h3
                                class="mt-4 text-base font-bold text-slate-900 dark:text-white"
                            >
                                Form masih kosong
                            </h3>

                            <p
                                class="mx-auto mt-1.5 max-w-sm text-sm leading-6 text-slate-500 dark:text-zinc-400"
                            >
                                Mulai dengan menambahkan pertanyaan atau bagian
                                untuk menyusun struktur form.
                            </p>

                            <button
                                type="button"
                                @click="openPickerAt(0)"
                                class="mt-5 inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_8px_25px_rgba(79,70,229,0.28)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_30px_rgba(79,70,229,0.35)]"
                            >
                                <IconPlus :size="17" />
                                Tambah Item
                            </button>
                        </div>

                        <draggable
                            v-else
                            :list="state.items"
                            item-key="_key"
                            handle=".drag-handle"
                            class="space-y-4"
                            ghost-class="opacity-40"
                            :animation="180"
                        >
                            <template #item="{ element: item, index }">
                                <div
                                    v-show="
                                        !renderItems[index]?.hiddenByCollapse
                                    "
                                >
                                    <SectionHeader
                                        v-if="item.kind === 'section'"
                                        :section="item"
                                        :is-first="isFirst(index)"
                                        :is-last="isLast(index)"
                                        @remove="removeItem(item)"
                                        @move-up="moveItem(item, 'up')"
                                        @move-down="moveItem(item, 'down')"
                                        @open-picker="openPickerAt(index + 1)"
                                    />

                                    <FieldCard
                                        v-else
                                        :field="item"
                                        :is-first="isFirst(index)"
                                        :is-last="isLast(index)"
                                        @remove="removeItem(item)"
                                        @duplicate="duplicateField(item)"
                                        @move-up="moveItem(item, 'up')"
                                        @move-down="moveItem(item, 'down')"
                                        @open-picker="openPickerAt(index + 1)"
                                    />
                                </div>
                            </template>
                        </draggable>

                        <button
                            v-if="state.items.length > 0"
                            type="button"
                            @click="openPickerAt(state.items.length)"
                            class="group mt-5 flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3.5 text-sm font-semibold text-slate-600 shadow-sm transition-all hover:border-indigo-400 hover:bg-white hover:text-indigo-700 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-400 dark:hover:border-indigo-400/60 dark:hover:bg-zinc-900 dark:hover:text-indigo-400"
                        >
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:bg-zinc-800 dark:text-zinc-400 dark:group-hover:bg-indigo-500/10 dark:group-hover:text-indigo-400"
                            >
                                <IconPlus :size="16" />
                            </span>
                            Tambah pertanyaan atau bagian
                        </button>
                    </div>
                </section>
            </div>
        </div>

        <div
            class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/90 shadow-bar backdrop-blur-xl dark:border-zinc-800/70 dark:bg-zinc-950/85 dark:shadow-black/20"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8"
            >
                <div class="hidden min-w-0 sm:block">
                    <p
                        class="truncate text-xs text-slate-500 dark:text-zinc-400"
                    >
                        <span
                            v-if="state.dirty"
                            class="font-semibold text-amber-600 dark:text-amber-400"
                        >
                            Perubahan belum disimpan
                        </span>
                        <span
                            v-else
                            class="font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            Semua perubahan sudah tersimpan
                        </span>
                    </p>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <a
                        :href="route('formulir.publik.show', form.slug)"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <Button
                            variant="secondary"
                            class="min-w-[44px] sm:min-w-0"
                        >
                            <template #icon>
                                <IconEye :size="17" stroke="1.75" />
                            </template>
                            <span class="hidden sm:inline">Pratinjau</span>
                        </Button>
                    </a>

                    <Button
                        variant="primary"
                        :disabled="state.saving"
                        class="min-w-[44px] sm:min-w-0"
                        @click="save(form.id)"
                    >
                        <template #icon>
                            <IconCheck
                                v-if="!state.dirty"
                                :size="17"
                                stroke="1.75"
                            />
                            <IconDeviceFloppy v-else :size="17" stroke="1.75" />
                        </template>
                        <span class="hidden sm:inline">
                            {{
                                state.saving
                                    ? "Menyimpan..."
                                    : state.dirty
                                      ? "Simpan Perubahan"
                                      : "Tersimpan"
                            }}
                        </span>
                        <span class="sm:hidden">
                            {{
                                state.saving
                                    ? "..."
                                    : state.dirty
                                      ? "Simpan"
                                      : "OK"
                            }}
                        </span>
                    </Button>
                </div>
            </div>
        </div>

        <FormMetaOffcanvas
            :open="metaOpen"
            :form="form"
            :sekolah-list="sekolahList"
            @close="metaOpen = false"
        />

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                leave-active-class="transition duration-150 ease-in"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="pickerIndex !== null"
                    class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 p-0 backdrop-blur-[2px] sm:items-center sm:p-4"
                    @click.self="closePicker"
                >
                    <Transition
                        appear
                        enter-active-class="transition duration-200 ease-out"
                        leave-active-class="transition duration-150 ease-in"
                        enter-from-class="translate-y-4 opacity-0 sm:scale-95"
                        leave-to-class="translate-y-4 opacity-0 sm:scale-95"
                    >
                        <div
                            class="w-full max-w-md rounded-t-3xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6 dark:bg-zinc-900 dark:shadow-black/40"
                        >
                            <div
                                class="mx-auto mb-5 h-1 w-10 rounded-full bg-slate-200 dark:bg-zinc-700 sm:hidden"
                            ></div>

                            <div class="mb-5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                                    >
                                        <IconPlus :size="20" stroke="2" />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-base font-bold text-slate-900 dark:text-white"
                                        >
                                            Tambah ke Form
                                        </h3>
                                        <p
                                            class="mt-0.5 text-xs text-slate-500 dark:text-zinc-400"
                                        >
                                            Pilih jenis item yang ingin
                                            ditambahkan
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <TypeAndSectionPicker
                                @pick-field="pickField"
                                @pick-section="pickSection"
                            />

                            <button
                                type="button"
                                @click="closePicker"
                                class="mt-4 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 dark:border-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                            >
                                Batal
                            </button>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </FormulirLayout>
</template>
