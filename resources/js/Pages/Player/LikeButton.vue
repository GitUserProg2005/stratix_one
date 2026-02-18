<script setup>
import { ref } from 'vue';

const props = defineProps({
    isLiked: { type: Boolean, default: false },
    absolute: { type: Boolean, default: true },
});

const emit = defineEmits(['toggle']);

const isBouncing = ref(false);

function toggleLike() {
    emit('toggle');
    isBouncing.value = true;
}

function onBounceEnd() {
    isBouncing.value = false;
}
</script>

<template>
    <button
        @click="toggleLike"
        type="button"
        :class="[
            'flex items-center gap-2 bg-white text-black px-4 py-2 rounded-full font-semibold hover:bg-gray-100 transition',
            props.absolute ? 'absolute top-2 right-2' : ''
        ]"
        title="Понравилось"
    >
        <span>Понравилось</span>
        <i
            :class="[
                props.isLiked ? 'fa-solid fa-heart' : 'fa-regular fa-heart',
                { 'heart-bounce': isBouncing }
            ]"
            class="inline-block"
            @animationend="onBounceEnd"
        ></i>
    </button>
</template>

<style scoped>
.heart-bounce {
    animation: heartBounce 0.4s ease;
}

@keyframes heartBounce {
    0%   { transform: scale(1); }
    40%  { transform: scale(1.35); }
    70%  { transform: scale(0.95); }
    100% { transform: scale(1); }
}
</style>
