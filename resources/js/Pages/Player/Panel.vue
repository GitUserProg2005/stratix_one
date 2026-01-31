<script setup>
import { ref } from 'vue';

import PlayBtn from './PlayBtn.vue';

const props = defineProps({
    track: {
        type: Array,
        required: false
    }
});

const volume = ref(40);
const trackProgress = ref(35);
</script>

<template>
    <section class="absolute w-full bottom-0 z-50 left-0 p-4 bg-body">
        <div class="flex items-center justify-between">
            <div class="hidden md:flex items-center h-full gap-4">
                <img :src="track?.preview" class="track-picture" alt="">
                <div class="flex flex-col justify-between">
                    <span class="title">{{ track?.title }}</span>
                    <span class="context">{{ track?.artist }}</span>
                </div>
                <button>
                    <i class="fa-solid fa-music"></i>
                </button>
            </div>

            <div class="flex flex-col gap-2">
                <div class="flex flex-row gap-4 lg:gap-8">
                    <button @click="prevTrack" class="">
                        <i class="fa-solid fa-backward"></i>
                    </button>

                    <PlayBtn :width="42" />

                    <button @click="prevTrack" class="">
                        <i class="fa-solid fa-forward"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-row gap-8">
                <button>
                    <i class="fa-solid fa-repeat"></i>
                </button>

                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-volume-low"></i>
                    <input type="range"
                        min="0" max="100"
                        v-model="volume"
                        class="volume-slider"
                    >
                    <span>{{ volume }}</span>
                </div> 
            </div>
        </div>

        <div class="w-full flex items-center gap-2 mt-2">
            <!-- Время текущего трека -->
            <span class="context w-8 text-left">1:12</span>

            <!-- Ползунок -->
            <div class="relative flex-1 h-1 bg-content rounded">
                <div 
                    class="absolute h-1 bg-white rounded"
                    :style="{ width: trackProgress + '%' }"
                ></div>

                <!-- Драггер -->
                <div 
                    class="absolute -top-1 w-3 h-3 bg-gray-200 rounded-full cursor-pointer"
                    :style="{ left: `calc(${trackProgress}% - 6px)` }"
                ></div>

                <!-- В реальной логике нужно будет отслеживать drag -->
                <input type="range"
                        min="0" max="100"
                        v-model="trackProgress"
                        class="absolute top-0 left-0 w-full h-1 opacity-0 cursor-pointer"
                >
            </div>

            <!-- Общая длина трека -->
            <span class="context w-8">3:42</span>
        </div>
    </section>
</template>