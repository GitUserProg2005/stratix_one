<script setup>
import { onMounted, onBeforeUnmount, ref, watch, computed } from 'vue';

import WaveVisualizer from './WaveVisualizer.vue';
import PlayBtn from './PlayBtn.vue';

const props = defineProps({
    track: {
        type: Object,
        required: false
    },
    playRightNow: Boolean
});

const volume = ref(40);
const trackProgress = ref(0);
const isPlaying = ref(false);
const isRepeating = ref(false);
const shouldAutoplay = computed(() => Boolean(props.playRightNow));

let audio = null;
let progressInterval = null;

watch(
  [() => props.track?.id, () => props.playRightNow],
  ([trackId, rightNow]) => {
    if (!props.track?.file) return;

    if (audio) {
        audio.pause();
        audio.src = ''; // полностью сбросить
        clearInterval(progressInterval);
        isPlaying.value = false;
    }

    audio = new Audio(props.track.file);
    audio.volume = volume.value / 100;
    audio.loop = isRepeating.value;
    trackProgress.value = 0;

    audio.addEventListener('ended', () => {
        if (!audio.loop) {
            isPlaying.value = false;
            clearInterval(progressInterval);
            trackProgress.value = 100;
        }
    });

    if (rightNow) {
      audio.play();
      isPlaying.value = true;

      progressInterval = setInterval(() => {
        if (audio.duration) {
          trackProgress.value = (audio.currentTime / audio.duration) * 100;
        }
      }, 500);
    }
  },
  { immediate: true }
);


watch(volume, (v) => {
    if (audio) audio.volume = v / 100;
});

onBeforeUnmount(() => {
    if (audio) {
        audio.pause();
        audio = null;
    }

    clearInterval(progressInterval);
});

function togglePlay() {
    if (!audio) return;
    if (isPlaying.value) {
        audio.pause();
        clearInterval(progressInterval);
    } else {
        audio.play();
        progressInterval = setInterval(() => {
            trackProgress.value = (audio.currentTime / audio.duration) * 100;
        }, 500);
    }

    isPlaying.value = !isPlaying.value;
}

function toggleRepeat() {
    isRepeating.value = !isRepeating.value;

    if (audio) {
        audio.loop = isRepeating.value;
    }
}

watch(trackProgress, (val) => {
    if (audio && Math.abs(audio.currentTime / audio.duration * 100 - val) > 1) {
        audio.currentTime = (val / 100) * audio.duration;
    }
});
</script>

<template>
    <section class="absolute w-full bottom-0 z-50 left-0 p-4 bg-body">
        <div class="flex items-center justify-between">
            <div class="hidden md:flex items-center h-full gap-4">
                <img :src="track?.preview_url" class="track-picture" alt="">
                <div class="flex flex-col justify-between">
                    <span class="title">{{ track?.title }}</span>
                    <span class="context">{{ track?.artist.name }}</span>
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

                    <PlayBtn @click="togglePlay" 
                        :width="42" 
                        :isPlaying="isPlaying"
                    />

                    <button @click="prevTrack" class="">
                        <i class="fa-solid fa-forward"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-row gap-8">
                <button @click="toggleRepeat" class="relative">
                    <i class="fa-solid fa-repeat"
                        :class="isRepeating ? 'text-green-500' : 'gray-400'"
                    ></i>

                    <span 
                        v-if="isRepeating"    
                        class="absolute -top-1 -right-2 text-[10px] font-bold text-green-500"
                    >
                        1
                    </span>
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

                <WaveVisualizer class="absolute bottom-1" />

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