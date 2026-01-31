<script setup>
import { computed } from 'vue';

const props = defineProps({
    width: {
        type: Number,
        required: true
    }
});

const size = computed(() => `${props.width}px`)
</script>

<template>
    <button
        class="primary-rounded-btn"
        :style="{ width: size, height: size }"
    >
        <i class="fa-solid fa-play"></i>
    </button>
</template>
