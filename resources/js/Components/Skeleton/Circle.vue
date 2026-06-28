<script setup>
defineProps({
    size: {
        type: String,
        default: '2.5rem',
    },
});
</script>

<template>
    <div
        class="skeleton-block relative shrink-0 overflow-hidden rounded-full bg-[var(--bg-card)]"
        :class="$attrs.class"
        :style="{ width: size, height: size }"
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
