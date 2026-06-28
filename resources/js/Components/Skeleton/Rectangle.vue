<script setup>
defineProps({
    width: {
        type: String,
        default: '100%',
    },
    height: {
        type: String,
        default: '1rem',
    },
    rounded: {
        type: String,
        default: 'rounded-lg',
    },
});
</script>

<template>
    <div
        class="skeleton-block relative shrink-0 overflow-hidden bg-[var(--bg-card)]"
        :class="[$attrs.class, rounded]"
        :style="{ width, height }"
        role="status"
        aria-label="Загрузка"
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div
                class="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-r from-transparent via-white/20 to-transparent dark:via-white/10 animate-shimmer-slide"
            />
        </div>
    </div>
</template>
