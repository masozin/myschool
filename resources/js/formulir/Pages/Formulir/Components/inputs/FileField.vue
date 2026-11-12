<!-- resources/js/Pages/Form/Components/inputs/FileField.vue -->
<script setup>
import { ref, computed, onBeforeUnmount } from "vue";
import ImageCropperModal from "@/Components/ImageCropperModal.vue";
import {
    IconUpload,
    IconX,
    IconAlertCircle,
    IconRefresh,
    IconEye,
    IconFileTypePdf,
    IconFileTypeDoc,
    IconFileTypeXls,
    IconFileTypeJpg,
    IconFileTypePng,
    IconFile,
} from "@tabler/icons-vue";

const props = defineProps({
    field: Object,
    modelValue: File,
    error: String,
    existingFile: Object,
});
const emit = defineEmits(["update:modelValue", "blur"]);

const fileName = ref(
    props.modelValue?.name ?? props.existingFile?.original_name ?? null,
);
const fileSize = ref(
    props.modelValue?.size ?? props.existingFile?.size ?? null,
);
const isExisting = ref(!props.modelValue && !!props.existingFile);
const dragging = ref(false);
const localFileError = ref(null);
const previewUrl = ref(props.existingFile?.url ?? null);

const allowedExtensions = computed(
    () => props.field.config?.validation?.extensions ?? null,
);
const maxSizeMb = computed(
    () => props.field.config?.validation?.max_size ?? null,
);

const acceptAttr = computed(() =>
    allowedExtensions.value?.length
        ? allowedExtensions.value.map((e) => `.${e}`).join(",")
        : undefined,
);

const IMAGE_EXTENSIONS = ["jpg", "jpeg", "png", "webp"];
const PREVIEWABLE_EXTENSIONS = [...IMAGE_EXTENSIONS, "pdf"];

const extension = computed(() =>
    fileName.value ? getExtension(fileName.value) : null,
);
const isImage = computed(() => IMAGE_EXTENSIONS.includes(extension.value));
const isPreviewable = computed(() =>
    PREVIEWABLE_EXTENSIONS.includes(extension.value),
);

const extensionIcon = computed(() => {
    const map = {
        pdf: IconFileTypePdf,
        doc: IconFileTypeDoc,
        docx: IconFileTypeDoc,
        xls: IconFileTypeXls,
        xlsx: IconFileTypeXls,
        jpg: IconFileTypeJpg,
        jpeg: IconFileTypeJpg,
        png: IconFileTypePng,
    };
    return map[extension.value] ?? IconFile;
});

function getExtension(filename) {
    return filename.split(".").pop().toLowerCase();
}

function formatSize(bytes) {
    if (!bytes && bytes !== 0) return null;
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function revokePreview() {
    if (previewUrl.value?.startsWith("blob:")) {
        URL.revokeObjectURL(previewUrl.value);
    }
}

function validateAndSet(file) {
    if (!file) return;

    localFileError.value = null;

    if (allowedExtensions.value?.length) {
        const ext = getExtension(file.name);
        if (!allowedExtensions.value.includes(ext)) {
            localFileError.value = `Jenis file harus : ${allowedExtensions.value.join(", ").toUpperCase()}.`;
            emit("update:modelValue", null);
            fileName.value = null;
            emit("blur");
            return;
        }
    }

    if (maxSizeMb.value && file.size > maxSizeMb.value * 1024 * 1024) {
        localFileError.value = `Ukuran file maksimal ${maxSizeMb.value}MB.`;
        emit("update:modelValue", null);
        fileName.value = null;
        emit("blur");
        return;
    }

    revokePreview();

    fileName.value = file.name;
    fileSize.value = file.size;
    isExisting.value = false;

    const ext = getExtension(file.name);
    previewUrl.value = PREVIEWABLE_EXTENSIONS.includes(ext)
        ? URL.createObjectURL(file)
        : null;

    emit("update:modelValue", file);
    emit("blur");
}

// ---- Crop untuk field bertipe gambar ----

const isImageField = computed(() => {
    const exts = props.field.config?.validation?.extensions;
    // Kalau tidak ada batasan ekstensi, atau ekstensinya semua gambar -> aktifkan crop
    return !exts || exts.every((e) => IMAGE_EXTENSIONS.includes(e));
});

const cropperOpen = ref(false);
const rawImageSrc = ref(null);

const cropAspectRatio = computed(() => {
    const ratio = props.field.config?.crop_ratio;
    if (!ratio || ratio === "none") return null;
    if (ratio === "free") return "free";
    return Number(ratio);
});

const shouldCrop = computed(() => cropAspectRatio.value !== null);

function handleFile(file) {
    if (!file) return;

    if (allowedExtensions.value?.length) {
        const ext = getExtension(file.name);
        if (!allowedExtensions.value.includes(ext)) {
            localFileError.value = `Jenis file harus : ${allowedExtensions.value.join(", ").toUpperCase()}.`;
            return;
        }
    }

    const ext = getExtension(file.name);

    if (shouldCrop.value && IMAGE_EXTENSIONS.includes(ext)) {
        rawImageSrc.value = URL.createObjectURL(file);
        cropperOpen.value = true;
        return;
    }

    validateAndSet(file);
}

function onCropped(croppedFile) {
    cropperOpen.value = false;
    if (rawImageSrc.value) URL.revokeObjectURL(rawImageSrc.value);
    rawImageSrc.value = null;
    validateAndSet(croppedFile); // ukuran hasil crop tetap dicek di sini
}

function onCropCancel() {
    cropperOpen.value = false;
    if (rawImageSrc.value) URL.revokeObjectURL(rawImageSrc.value);
    rawImageSrc.value = null;
}

function onDrop(e) {
    dragging.value = false;
    handleFile(e.dataTransfer.files[0]);
}

function openPreview() {
    if (previewUrl.value) window.open(previewUrl.value, "_blank", "noopener");
}

function clear() {
    revokePreview();
    fileName.value = null;
    fileSize.value = null;
    previewUrl.value = null;
    isExisting.value = false;
    localFileError.value = null;
    emit("update:modelValue", null);
}

onBeforeUnmount(revokePreview);

const displayError = computed(() => localFileError.value ?? props.error);
const displaySize = computed(() => formatSize(fileSize.value));
</script>

<template>
    <div>
        <!-- DROPZONE (belum ada file) -->
        <label
            v-if="!fileName"
            @dragover.prevent="dragging = true"
            @dragleave="dragging = false"
            @drop.prevent="onDrop"
            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed py-8 text-center transition-colors duration-200 ease-out"
            :class="[
                dragging
                    ? 'border-brand bg-brand/5'
                    : 'border-border bg-canvas',
                displayError ? 'border-danger' : '',
            ]"
        >
            <IconUpload :size="22" stroke="1.5" class="text-indigo-500" />
            <span class="text-sm text-muted">
                Seret file ke sini atau
                <span class="font-semibold text-indigo-700">pilih file</span>
            </span>
            <span
                v-if="allowedExtensions || maxSizeMb"
                class="text-xs text-muted/70"
            >
                <template v-if="allowedExtensions"
                    >Format:
                    {{ allowedExtensions.join(", ").toUpperCase() }}</template
                >
                <template v-if="allowedExtensions && maxSizeMb"> · </template>
                <template v-if="maxSizeMb">Maks {{ maxSizeMb }}MB</template>
            </span>
            <input
                type="file"
                class="hidden"
                :accept="acceptAttr"
                @change="handleFile($event.target.files[0])"
            />
        </label>

        <!-- FILE TERPILIH -->
        <div
            v-else
            class="flex items-center gap-3 rounded-xl border border-slate-300 bg-white p-3 shadow-sm"
        >
            <button
                v-if="isPreviewable && previewUrl"
                type="button"
                @click="openPreview"
                class="group relative h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-border bg-canvas"
                title="Lihat pratinjau"
            >
                <img
                    v-if="isImage"
                    :src="previewUrl"
                    class="h-full w-full object-cover"
                    alt=""
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center text-brand"
                >
                    <component :is="extensionIcon" :size="22" stroke="1.5" />
                </div>
                <div
                    class="absolute inset-0 flex items-center justify-center bg-ink/0 opacity-0 transition-all duration-150 group-hover:bg-ink/40 group-hover:opacity-100"
                >
                    <IconEye :size="16" class="text-white" />
                </div>
            </button>

            <div
                v-else
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-canvas text-brand"
            >
                <component :is="extensionIcon" :size="22" stroke="1.5" />
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-ink">
                    {{ fileName }}
                </p>
                <p class="mt-0.5 text-xs text-muted">
                    <span v-if="displaySize">{{ displaySize }}</span>
                    <span v-if="displaySize && isExisting"> · </span>
                    <span v-if="isExisting">File lama</span>
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <button
                    v-if="isPreviewable && previewUrl"
                    type="button"
                    @click="openPreview"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-muted transition-colors duration-150 hover:bg-canvas hover:text-brand"
                    title="Lihat pratinjau"
                >
                    <IconEye :size="16" />
                </button>
                <label
                    class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors duration-150 hover:bg-canvas hover:text-brand"
                    title="Ganti file"
                >
                    <IconRefresh :size="16" />
                    <input
                        type="file"
                        class="hidden"
                        :accept="acceptAttr"
                        @change="handleFile($event.target.files[0])"
                    />
                </label>
                <button
                    type="button"
                    @click="clear"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-muted transition-colors duration-150 hover:bg-danger/10 hover:text-danger"
                    title="Hapus file"
                >
                    <IconX :size="16" />
                </button>
            </div>
        </div>

        <p
            v-if="localFileError"
            class="mt-1.5 flex items-center gap-1 text-sm text-danger"
        >
            <IconAlertCircle :size="14" /> {{ localFileError }}
        </p>

        <!-- SATU-SATUNYA instance modal crop, di luar <label> mana pun -->
        <ImageCropperModal
            :open="cropperOpen"
            :image-src="rawImageSrc"
            :aspect-ratio="cropAspectRatio"
            @close="onCropCancel"
            @cropped="onCropped"
        />
    </div>
</template>
