<script setup>
import { computed } from 'vue';

const props = defineProps({
    width: {
        type: Number,
        required: true
    },
    isPlaying: Boolean
});

const size = computed(() => `${props.width}px`)
</script>

<template>
    <button
        class="primary-rounded-btn"
        :style="{ width: size, height: size }"
    >
        <i class="fa-solid"
            :class="{ 'fa-play' : !isPlaying, 'fa-pause' : isPlaying }"
        ></i>
    </button>
</template>
