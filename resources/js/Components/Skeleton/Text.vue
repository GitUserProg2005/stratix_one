<script setup>
import Rectangle from './Rectangle.vue';

defineProps({
    lines: {
        type: Number,
        default: 3,
    },
    lineHeight: {
        type: String,
        default: '0.75rem',
    },
    gap: {
        type: String,
        default: '0.5rem',
    },
    lastLineWidth: {
        type: String,
        default: '60%',
    },
});
</script>

<template>
    <div class="flex w-full flex-col" :style="{ gap }">
        <Rectangle
            v-for="line in lines"
            :key="line"
            :height="lineHeight"
            :width="line === lines ? lastLineWidth : '100%'"
            rounded="rounded-md"
        />
    </div>
</template>
