<!-- resources/js/Components/Button.vue -->
<script setup>
defineProps({
    variant: { type: String, default: "primary" },
    type: { type: String, default: "button" },
    disabled: { type: Boolean, default: false },
});

const variants = {
    primary:
        "bg-brand text-white hover:bg-brand-dark disabled:bg-brand/50 shadow-glow",
    secondary:
        "bg-surface dark:bg-surface-dark text-ink dark:text-ink-dark border border-border dark:border-border-dark hover:bg-canvas dark:hover:bg-zinc-800",
    ghost: "text-muted dark:text-muted-dark hover:text-ink dark:hover:text-ink-dark hover:bg-canvas dark:hover:bg-zinc-800",
    danger: "bg-surface dark:bg-surface-dark text-red-500 dark:text-red-400 border border-red-200 dark:border-red-500/30 hover:bg-red-50 dark:hover:bg-red-500/10",
};
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex min-h-[40px] items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 hover:-translate-y-px focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500/30 active:scale-[.98] disabled:cursor-not-allowed"
        :class="variants[variant]"
    >
        <slot name="icon" />
        <slot />
    </button>
</template>
