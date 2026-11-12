<!-- resources/js/Components/ImageCropperModal.vue -->
<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { IconX, IconCheck, IconZoomIn, IconZoomOut } from "@tabler/icons-vue";

const props = defineProps({
    open: Boolean,
    imageSrc: String,
    aspectRatio: { type: [Number, String, null], default: null }, // null/'free' = bebas, angka = rasio tetap
});
const emit = defineEmits(["close", "cropped"]);

const STAGE_SIZE = 340; // px, area kerja modal

const isFreeMode = computed(
    () => !props.aspectRatio || props.aspectRatio === "free",
);
const fixedRatio = computed(() =>
    isFreeMode.value ? null : Number(props.aspectRatio),
);

const imgEl = ref(null);
const naturalSize = ref({ w: 0, h: 0 });
const imgDisplaySize = ref({ w: 0, h: 0 });
const scale = ref(1);
const minScale = ref(1);

// Posisi & ukuran crop box, dalam koordinat STAGE (bukan koordinat gambar asli)
const cropBox = ref({ x: 0, y: 0, w: 200, h: 200 });

const draggingImage = ref(false);
const imageOffset = ref({ x: 0, y: 0 });
const dragStart = ref({ x: 0, y: 0 });
const offsetStart = ref({ x: 0, y: 0 });

const resizing = ref(null); // 'nw' | 'ne' | 'sw' | 'se' | null
const resizeStart = ref({ x: 0, y: 0, box: null });

function onImageLoad() {
    naturalSize.value = {
        w: imgEl.value.naturalWidth,
        h: imgEl.value.naturalHeight,
    };

    // Gambar tampil penuh di stage (fit), user bisa zoom in dari sini
    const fitScale = Math.min(
        STAGE_SIZE / naturalSize.value.w,
        STAGE_SIZE / naturalSize.value.h,
    );
    minScale.value = fitScale;
    scale.value = fitScale;
    imgDisplaySize.value = {
        w: naturalSize.value.w * fitScale,
        h: naturalSize.value.h * fitScale,
    };
    imageOffset.value = { x: 0, y: 0 };

    // Crop box awal: tengah stage, ukuran menyesuaikan rasio (atau kotak default kalau bebas)
    resetCropBox();
}

function resetCropBox() {
    const maxBoxSize =
        Math.min(imgDisplaySize.value.w, imgDisplaySize.value.h) * 0.9;

    let w, h;
    if (fixedRatio.value) {
        if (fixedRatio.value >= 1) {
            w = maxBoxSize;
            h = maxBoxSize / fixedRatio.value;
        } else {
            h = maxBoxSize;
            w = maxBoxSize * fixedRatio.value;
        }
    } else {
        w = maxBoxSize;
        h = maxBoxSize;
    }

    cropBox.value = {
        x: (STAGE_SIZE - w) / 2,
        y: (STAGE_SIZE - h) / 2,
        w,
        h,
    };
}

watch(
    () => props.open,
    (val) => {
        if (val)
            nextTick(() => {
                scale.value = minScale.value;
                imageOffset.value = { x: 0, y: 0 };
            });
    },
);

// ---- Geser gambar (bukan crop box) — drag di area luar crop box, atau selalu drag gambar di belakang ----
function startDragImage(e) {
    if (resizing.value) return;
    draggingImage.value = true;
    const p = e.touches ? e.touches[0] : e;
    dragStart.value = { x: p.clientX, y: p.clientY };
    offsetStart.value = { ...imageOffset.value };
}

function onDragImage(e) {
    if (!draggingImage.value) return;
    const p = e.touches ? e.touches[0] : e;
    imageOffset.value = {
        x: offsetStart.value.x + (p.clientX - dragStart.value.x),
        y: offsetStart.value.y + (p.clientY - dragStart.value.y),
    };
}

function stopDragImage() {
    draggingImage.value = false;
}

function zoomIn() {
    scale.value = Math.min(scale.value * 1.15, minScale.value * 5);
    syncDisplaySize();
}
function zoomOut() {
    scale.value = Math.max(scale.value / 1.15, minScale.value);
    syncDisplaySize();
}
function syncDisplaySize() {
    imgDisplaySize.value = {
        w: naturalSize.value.w * scale.value,
        h: naturalSize.value.h * scale.value,
    };
}

// ---- Resize crop box lewat handle sudut ----
function startResize(corner, e) {
    e.stopPropagation();
    resizing.value = corner;
    const p = e.touches ? e.touches[0] : e;
    resizeStart.value = {
        x: p.clientX,
        y: p.clientY,
        box: { ...cropBox.value },
    };
}

function onResize(e) {
    if (!resizing.value) return;
    const p = e.touches ? e.touches[0] : e;
    const dx = p.clientX - resizeStart.value.x;
    const dy = p.clientY - resizeStart.value.y;
    const start = resizeStart.value.box;

    let { x, y, w, h } = start;

    if (resizing.value.includes("e")) w = start.w + dx;
    if (resizing.value.includes("w")) {
        w = start.w - dx;
        x = start.x + dx;
    }
    if (resizing.value.includes("s")) h = start.h + dy;
    if (resizing.value.includes("n")) {
        h = start.h - dy;
        y = start.y + dy;
    }

    // Kalau rasio tetap, kunci tinggi mengikuti lebar
    if (fixedRatio.value) {
        h = w / fixedRatio.value;
        if (resizing.value.includes("n")) y = start.y + start.h - h;
    }

    // Batas minimum & tidak boleh keluar stage
    w = Math.max(40, Math.min(w, STAGE_SIZE));
    h = Math.max(40, Math.min(h, STAGE_SIZE));
    x = Math.max(0, Math.min(x, STAGE_SIZE - w));
    y = Math.max(0, Math.min(y, STAGE_SIZE - h));

    cropBox.value = { x, y, w, h };
}

function stopResize() {
    resizing.value = null;
}

function onStageMouseMove(e) {
    onDragImage(e);
    onResize(e);
}
function onStageMouseUp() {
    stopDragImage();
    stopResize();
}

// ---- Render hasil crop ----
function confirmCrop() {
    const outputWidth = 1200;
    const outputScale = outputWidth / cropBox.value.w;
    const outputHeight = Math.round(cropBox.value.h * outputScale);

    const canvas = document.createElement("canvas");
    canvas.width = outputWidth;
    canvas.height = outputHeight;
    const ctx = canvas.getContext("2d");

    // Posisi gambar di stage: tengah stage + offset user
    const imgLeft =
        (STAGE_SIZE - imgDisplaySize.value.w) / 2 + imageOffset.value.x;
    const imgTop =
        (STAGE_SIZE - imgDisplaySize.value.h) / 2 + imageOffset.value.y;

    // Area crop box relatif terhadap gambar (dalam skala tampilan), lalu dikonversi ke skala natural gambar
    const cropRelX = (cropBox.value.x - imgLeft) / scale.value;
    const cropRelY = (cropBox.value.y - imgTop) / scale.value;
    const cropW = cropBox.value.w / scale.value;
    const cropH = cropBox.value.h / scale.value;

    ctx.drawImage(
        imgEl.value,
        cropRelX,
        cropRelY,
        cropW,
        cropH,
        0,
        0,
        outputWidth,
        outputHeight,
    );

    canvas.toBlob(
        (blob) => {
            const file = new File([blob], "cropped.jpg", {
                type: "image/jpeg",
            });
            emit("cropped", file);
        },
        "image/jpeg",
        0.9,
    );
}

function skipCrop() {
    // Tombol "Gunakan Tanpa Crop" — kirim balik file asli tanpa diproses
    fetch(props.imageSrc)
        .then((r) => r.blob())
        .then((blob) => {
            const file = new File([blob], "original.jpg", { type: blob.type });
            emit("cropped", file);
        });
}

const CORNERS = ["nw", "ne", "sw", "se"];
const cornerCursor = {
    nw: "nwse-resize",
    se: "nwse-resize",
    ne: "nesw-resize",
    sw: "nesw-resize",
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-150"
            leave-active-class="transition-opacity duration-100"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-ink/60 p-4"
            >
                <div
                    class="w-full max-w-md rounded-2xl bg-surface p-5 shadow-premium dark:shadow-premium-dark"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h3
                            class="font-display text-base font-semibold text-ink"
                        >
                            {{
                                isFreeMode
                                    ? "Sesuaikan Gambar (Bebas)"
                                    : "Sesuaikan Gambar"
                            }}
                        </h3>
                        <button
                            @click="$emit('close')"
                            class="text-muted hover:text-ink"
                        >
                            <IconX :size="18" />
                        </button>
                    </div>

                    <!-- Stage -->
                    <div
                        class="relative mx-auto select-none overflow-hidden rounded-lg border border-border bg-canvas"
                        :style="{
                            width: `${STAGE_SIZE}px`,
                            height: `${STAGE_SIZE}px`,
                            touchAction: 'none',
                        }"
                        @mousedown="startDragImage"
                        @mousemove="onStageMouseMove"
                        @mouseup="onStageMouseUp"
                        @mouseleave="onStageMouseUp"
                        @touchstart="startDragImage"
                        @touchmove="onStageMouseMove"
                        @touchend="onStageMouseUp"
                    >
                        <!-- Gambar -->
                        <img
                            ref="imgEl"
                            :src="imageSrc"
                            @load="onImageLoad"
                            class="pointer-events-none absolute left-1/2 top-1/2 max-w-none"
                            :style="{
                                width: `${imgDisplaySize.w}px`,
                                height: `${imgDisplaySize.h}px`,
                                transform: `translate(calc(-50% + ${imageOffset.x}px), calc(-50% + ${imageOffset.y}px))`,
                            }"
                            draggable="false"
                        />

                        <!-- Overlay gelap di luar crop box -->
                        <div
                            class="pointer-events-none absolute inset-0 bg-ink/50"
                            :style="{
                                clipPath: `polygon(0 0, 100% 0, 100% 100%, 0 100%, 0 ${cropBox.y}px, ${cropBox.x}px ${cropBox.y}px, ${cropBox.x}px ${cropBox.y + cropBox.h}px, ${cropBox.x + cropBox.w}px ${cropBox.y + cropBox.h}px, ${cropBox.x + cropBox.w}px ${cropBox.y}px, 0 ${cropBox.y}px)`,
                            }"
                        ></div>

                        <!-- Crop box -->
                        <div
                            class="absolute border-2 border-white"
                            :style="{
                                left: `${cropBox.x}px`,
                                top: `${cropBox.y}px`,
                                width: `${cropBox.w}px`,
                                height: `${cropBox.h}px`,
                                cursor: 'move',
                            }"
                        >
                            <!-- Handle resize di 4 sudut, hanya tampil kalau mode bebas atau tetap tapi masih boleh diubah ukurannya -->
                            <div
                                v-for="corner in CORNERS"
                                :key="corner"
                                class="absolute h-3.5 w-3.5 rounded-full border-2 border-brand bg-white"
                                :style="{
                                    cursor: cornerCursor[corner],
                                    top: corner.includes('n') ? '-7px' : 'auto',
                                    bottom: corner.includes('s')
                                        ? '-7px'
                                        : 'auto',
                                    left: corner.includes('w')
                                        ? '-7px'
                                        : 'auto',
                                    right: corner.includes('e')
                                        ? '-7px'
                                        : 'auto',
                                }"
                                @mousedown="startResize(corner, $event)"
                                @touchstart="startResize(corner, $event)"
                            ></div>
                        </div>
                    </div>

                    <!-- Zoom controls -->
                    <div class="mt-4 flex items-center justify-center gap-3">
                        <button
                            @click="zoomOut"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-border text-muted hover:bg-canvas"
                        >
                            <IconZoomOut :size="16" />
                        </button>
                        <input
                            type="range"
                            :min="minScale"
                            :max="minScale * 5"
                            :step="0.01"
                            v-model.number="scale"
                            @input="syncDisplaySize"
                            class="w-32"
                        />
                        <button
                            @click="zoomIn"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-border text-muted hover:bg-canvas"
                        >
                            <IconZoomIn :size="16" />
                        </button>
                    </div>

                    <p class="mt-2 text-center text-xs text-muted">
                        {{
                            isFreeMode
                                ? "Seret sudut untuk ubah bentuk, seret tengah untuk geser"
                                : "Seret gambar untuk mengatur posisi"
                        }}
                    </p>

                    <div
                        class="mt-5 flex justify-end gap-2 border-t border-border pt-4"
                    >
                        <button
                            @click="$emit('close')"
                            class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-ink hover:bg-canvas"
                        >
                            Batal
                        </button>
                        <button
                            @click="confirmCrop"
                            class="flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark"
                        >
                            <IconCheck :size="15" /> Gunakan Gambar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
