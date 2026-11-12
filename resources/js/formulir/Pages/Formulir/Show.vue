<!-- resources/js/Pages/Form/Show.vue -->
<script setup>
import { computed, ref } from "vue";
import { usePage, useForm, Link } from "@inertiajs/vue3";
import { formatDate, parseISO } from "@/utils/dateFormat";

import SubmissionSuccessModal from "./Components/SubmissionSuccessModal.vue";
import FormLayout from "@/Layouts/FormLayout.vue";
import FieldRenderer from "./Components/FieldRenderer.vue";
import Button from "@/Components/Button.vue";

import { IconSend, IconLock, IconArrowLeft } from "@tabler/icons-vue";

const page = usePage();

const successPayload = computed(
    () => page.props.flash?.success_submission ?? null,
);

const props = defineProps({
    form: Object,
    submission: Object,
});

const isEditMode = computed(() => !!props.submission);

const answerableFields = computed(() => {
    const loose = props.form.fields ?? [];
    const sectioned = (props.form.sections ?? []).flatMap(
        (section) => section.fields ?? [],
    );
    return [...loose, ...sectioned].filter(
        (field) => !["heading", "paragraph"].includes(field.type),
    );
});

const initialData = Object.fromEntries(
    answerableFields.value
        .filter((field) => field.type !== "file")
        .map((field) => [
            field.name,
            props.submission?.data?.[field.name] ??
                (field.type === "checkbox" ? [] : ""),
        ]),
);

const answers = ref(initialData);

const formState = useForm({
    files: Object.fromEntries(
        answerableFields.value
            .filter((field) => field.type === "file")
            .map((field) => [field.name, null]),
    ),
});

const answeredCount = computed(() => {
    let count = 0;

    for (const field of answerableFields.value) {
        let value;

        if (field.type === "file") {
            value =
                formState.files[field.name] ||
                props.submission?.files?.[field.name];
        } else {
            value = answers.value[field.name];
        }

        if (Array.isArray(value)) {
            if (value.length > 0) count++;
        } else {
            if (
                value !== null &&
                value !== undefined &&
                String(value).trim() !== ""
            ) {
                count++;
            }
        }
    }

    return count;
});

function updateField(field, value) {
    if (field.type === "file") {
        formState.files[field.name] = value;
    } else {
        answers.value[field.name] = value;
    }
}

function fieldValue(field) {
    if (field.type === "file") {
        return formState.files[field.name];
    }
    return answers.value[field.name];
}

function fieldError(field) {
    const key =
        field.type === "file" ? `files.${field.name}` : `data.${field.name}`;
    return formState.errors[key];
}

function existingFileFor(field) {
    return props.submission?.files?.[field.name] ?? null;
}

function submit() {
    const options = {
        forceFormData: true,
        onError: () => {
            const firstErrorKey = Object.keys(formState.errors)[0];
            if (firstErrorKey) {
                document
                    .getElementById(`field-${firstErrorKey.split(".").pop()}`)
                    ?.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        },
    };

    const payload = {
        data: answers.value,
        files: formState.files,
    };

    if (isEditMode.value) {
        formState
            .transform(() => ({ ...payload, _method: "put" }))
            .post(
                route("formulir.publik.update", [
                    props.form.slug,
                    props.submission.id,
                ]),
                options,
            );
        return;
    }

    formState
        .transform(() => payload)
        .post(route("formulir.publik.simpan", props.form.slug), options);
}
</script>

<template>
    <FormLayout
        :end-at="form.end_at"
        :progress="{
            answered: answeredCount,
            total: answerableFields.length,
        }"
    >
        <!-- BANNER -->
        <template #banner>
            <div
                v-if="form.banner_image"
                class="relative h-48 w-full overflow-hidden sm:h-64"
            >
                <img
                    :src="`/storage/${form.banner_image}`"
                    class="h-full w-full object-cover"
                    :alt="form.title"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-slate-950/10 to-transparent"
                ></div>
            </div>
        </template>

        <!-- FORM DITUTUP -->
        <div
            v-if="!form.status || form.status === 'closed'"
            class="flex flex-col items-center rounded-xl border border-slate-200 bg-white px-6 py-14 text-center dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div
                class="flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400"
            >
                <IconLock :size="26" stroke="1.5" />
            </div>

            <h2 class="mt-4 text-xl font-bold text-slate-900 dark:text-white">
                Form ini sudah ditutup
            </h2>
        </div>

        <!-- FORM AKTIF -->
        <template v-else>
            <Link
                v-if="isEditMode && form.allow_multiple_submissions"
                :href="route('formulir.publik.show', form.slug)"
                class="mb-4 flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white"
            >
                <IconArrowLeft :size="16" />
                Kembali ke Riwayat Jawaban
            </Link>

            <div v-if="!form.banner_image" class="mb-6">
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

            <div
                v-if="isEditMode"
                class="mb-6 rounded-lg bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
            >
                Kamu sedang mengedit jawaban yang sudah dikirim sebelumnya.
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- FIELD TANPA SECTION -->
                <template v-for="field in form.fields" :key="field.id">
                    <div
                        v-if="['heading', 'paragraph'].includes(field.type)"
                        class="px-1"
                    >
                        <FieldRenderer
                            :field="field"
                            :model-value="fieldValue(field)"
                            :error="fieldError(field)"
                            :existing-file="existingFileFor(field)"
                            @update:model-value="
                                (value) => updateField(field, value)
                            "
                        />
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-slate-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 sm:p-5"
                    >
                        <FieldRenderer
                            :field="field"
                            :model-value="fieldValue(field)"
                            :error="fieldError(field)"
                            :existing-file="existingFileFor(field)"
                            @update:model-value="
                                (value) => updateField(field, value)
                            "
                        />
                    </div>
                </template>

                <!-- SECTION -->
                <div
                    v-for="section in form.sections"
                    :key="section.id"
                    class="space-y-4"
                >
                    <div
                        class="border-b border-slate-200 pb-3 dark:border-zinc-800"
                    >
                        <h2
                            class="text-lg font-bold text-slate-900 dark:text-white"
                        >
                            {{ section.title }}
                        </h2>
                        <p
                            v-if="section.description"
                            class="mt-1 text-sm text-slate-500 dark:text-zinc-400"
                        >
                            {{ section.description }}
                        </p>
                    </div>

                    <template v-for="field in section.fields" :key="field.id">
                        <div
                            v-if="['heading', 'paragraph'].includes(field.type)"
                            class="px-1"
                        >
                            <FieldRenderer
                                :field="field"
                                :model-value="fieldValue(field)"
                                :error="fieldError(field)"
                                :existing-file="existingFileFor(field)"
                                @update:model-value="
                                    (value) => updateField(field, value)
                                "
                            />
                        </div>

                        <div
                            v-else
                            class="rounded-xl border border-slate-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 sm:p-5"
                        >
                            <FieldRenderer
                                :field="field"
                                :model-value="fieldValue(field)"
                                :error="fieldError(field)"
                                :existing-file="existingFileFor(field)"
                                @update:model-value="
                                    (value) => updateField(field, value)
                                "
                            />
                        </div>
                    </template>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="sticky bottom-4 pt-2">
                    <Button
                        type="submit"
                        variant="primary"
                        :disabled="formState.processing"
                        class="w-full justify-center py-3 shadow-lg"
                    >
                        <template #icon>
                            <IconSend :size="18" stroke="1.75" />
                        </template>

                        {{
                            formState.processing
                                ? "Menyimpan..."
                                : isEditMode
                                  ? "Simpan Perubahan"
                                  : "Kirim Jawaban"
                        }}
                    </Button>
                </div>
            </form>
        </template>
    </FormLayout>

    <SubmissionSuccessModal :payload="successPayload" />
</template>
