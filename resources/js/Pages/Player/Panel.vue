<script setup>
import { ref, watch, onBeforeUnmount, nextTick } from 'vue';
import Hls from 'hls.js';

import WaveVisualizer from './WaveVisualizer.vue';
import PlayBtn from './PlayBtn.vue';
import KaraokeLyrics from '@/Components/KaraokeLyrics.vue';
import { stopListen } from '@/utils/stopListen.js';

const props = defineProps({
    track: { type: Object, default: null },
    playRightNow: Boolean,
});

const volume = ref(40);
const trackProgress = ref(0);
const isPlaying = ref(false);
const isRepeating = ref(false);
const currentTimeFormatted = ref('0:00');
const durationFormatted = ref('0:00');
const currentTimeSeconds = ref(0);

const audioRef = ref(null);
let hlsInstance = null;
let progressInterval = null;

const hasSource = () => props.track?.hls_url || props.track?.file;

function formatTime(seconds) {
    if (!Number.isFinite(seconds) || seconds < 0) return '0:00';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
}

function updateTimeDisplay() {
    const el = audioRef.value;
    if (!el) return;
    const now = el.currentTime;
    currentTimeSeconds.value = now;
    currentTimeFormatted.value = formatTime(now);
    const dur = el.duration;
    if (Number.isFinite(dur)) {
        durationFormatted.value = formatTime(dur);
        trackProgress.value = dur > 0 ? (el.currentTime / dur) * 100 : 0;
    }
}

function startProgressInterval() {
    stopProgressInterval();
    progressInterval = setInterval(updateTimeDisplay, 250);
}

function stopProgressInterval() {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
}

function cleanupMedia() {
    if (hlsInstance) {
        hlsInstance.destroy();
        hlsInstance = null;
    }
    
    const el = audioRef.value;

    if (el) {
        el.pause();
        el.removeAttribute('src');
        el.load();
    }

    stopProgressInterval();

    isPlaying.value = false;
    trackProgress.value = 0;
    currentTimeSeconds.value = 0;
    currentTimeFormatted.value = '0:00';
    durationFormatted.value = '0:00';
}

function initSource() {
    if (!audioRef.value || !hasSource()) return;

    const el = audioRef.value;
    el.volume = volume.value / 100;
    el.loop = isRepeating.value;

    if (props.track.hls_url) {
        if (Hls.isSupported()) {
            hlsInstance = new Hls();
            hlsInstance.loadSource(props.track.hls_url);
            hlsInstance.attachMedia(el);
            hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => nextTick(updateTimeDisplay));
        } else {
            // Safari и др. с нативной поддержкой HLS
            el.src = props.track.hls_url;
        }
    } else if (props.track.file) {
        el.src = props.track.file;
    }

    el.addEventListener('ended', () => {
        if (!el.loop) {
            const dur = el.duration;
            if (Number.isFinite(dur) && props.track?.id) {
                stopListen('ended', el.currentTime, dur, props.track.id);
            }
            isPlaying.value = false;
            stopProgressInterval();
            trackProgress.value = 100;
            updateTimeDisplay();
        }
    });

    el.addEventListener('durationchange', updateTimeDisplay);
    el.addEventListener('timeupdate', updateTimeDisplay);

    if (props.playRightNow) {
        el.play().then(() => {
            isPlaying.value = true;
            startProgressInterval();
        });
    }
}

watch(
    () => [props.track?.id, props.playRightNow],
    ([trackId, rightNow]) => {
        if (!hasSource()) return;
        cleanupMedia();
        nextTick(() => {
            initSource();
        });
    },
    { immediate: true }
);

watch(volume, (v) => {
    if (audioRef.value) audioRef.value.volume = v / 100;
});

onBeforeUnmount(() => {
    cleanupMedia();
});

function togglePlay() {
    const el = audioRef.value;
    if (!el || !hasSource()) return;
    if (isPlaying.value) {
        el.pause();
        stopProgressInterval();
    } else {
        el.play();
        startProgressInterval();
    }
    isPlaying.value = !isPlaying.value;
}

function toggleRepeat() {
    isRepeating.value = !isRepeating.value;
    if (audioRef.value) audioRef.value.loop = isRepeating.value;
}

function seekTo(time) {
    const el = audioRef.value;
    if (!el) return;
    const t = Number(time);
    if (!Number.isFinite(t) || t < 0) return;
    el.currentTime = t;
    if (el.paused) {
        el.play();
        isPlaying.value = true;
        startProgressInterval();
    }
    updateTimeDisplay();
}

function onProgressInput() {
    const el = audioRef.value;
    if (!el || !Number.isFinite(el.duration) || el.duration <= 0) return;
    const percent = trackProgress.value;
    const seek = (Number(percent) / 100) * el.duration;
    const currentPercent = (el.currentTime / el.duration) * 100;
    if (Math.abs(currentPercent - percent) > 0.5) {
        el.currentTime = seek;
        updateTimeDisplay();
    }
}

/** Для кнопки «Назад»: текущее время и длительность трека. */
function getListenState() {
    const el = audioRef.value;
    if (!el || !props.track?.id) return null;
    const dur = el.duration;
    if (!Number.isFinite(dur)) return null;
    return { listenTime: currentTimeSeconds.value, duration: dur };
}

defineExpose({ getListenState });
</script>

<template>
    <section class="absolute w-full bottom-0 z-50 left-0 p-4 bg-body">
        <audio ref="audioRef" preload="metadata" />

        <div class="flex items-center justify-between">
            <div class="flex items-center h-full gap-4">
                <div class="hidden lg:flex flex-row items-center gap-4">
                    <img :src="track?.preview_url" class="track-picture" alt="">
                    <div class="flex flex-col justify-between">
                        <span class="title">{{ track?.title }}</span>
                        <span class="context">{{ track?.artist?.name }}</span>
                    </div>
                </div>

                <KaraokeLyrics
                    :current-time="currentTimeSeconds"
                    :lyrics="track?.lyrics"
                    @seek="seekTo"
                />
            </div>

            <div class="flex flex-col gap-2">
                <div class="flex flex-row gap-4 lg:gap-8 items-center">
                    <button type="button" class="p-2 text-gray-400 hover:text-white" aria-label="Предыдущий">
                        <i class="fa-solid fa-backward"></i>
                    </button>
                    <PlayBtn :width="42" :is-playing="isPlaying" @click="togglePlay" />
                    <button type="button" class="p-2 text-gray-400 hover:text-white" aria-label="Следующий">
                        <i class="fa-solid fa-forward"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-row gap-8 items-center">
                <button type="button" class="relative p-2" @click="toggleRepeat" aria-label="Повтор">
                    <i class="fa-solid fa-repeat" :class="isRepeating ? 'text-green-500' : 'text-gray-400'"></i>
                    <span v-if="isRepeating" class="absolute -top-0.5 -right-1 text-[10px] font-bold text-green-500">1</span>
                </button>
                <div class="hidden lg:flex items-center gap-2">
                    <i class="fa-solid fa-volume-low"></i>
                    <input v-model.number="volume" type="range" min="0" max="100" class="volume-slider">
                    <span class="context w-6">{{ volume }}</span>
                </div>
            </div>
        </div>

        <div class="w-full flex items-center gap-2 mt-2">
            <span class="context w-10 text-left tabular-nums">{{ currentTimeFormatted }}</span>
            <div class="relative flex-1 h-1 bg-content rounded flex items-center">
                <div class="absolute h-1 bg-white rounded left-0" :style="{ width: trackProgress + '%' }" />
                <WaveVisualizer class="absolute bottom-0.5 pointer-events-none" />
                
                <div
                    class="absolute -top-1 w-3 h-3 bg-gray-200 rounded-full pointer-events-none"
                    :style="{ left: `calc(${trackProgress}% - 6px)` }"
                />
                <input
                    v-model.number="trackProgress"
                    type="range"
                    min="0"
                    max="100"
                    step="any"
                    class="absolute inset-0 w-full h-2 opacity-0 cursor-pointer"
                    @input="onProgressInput"
                >
            </div>
            <span class="context w-10 text-right tabular-nums">{{ durationFormatted }}</span>
        </div>
    </section>
</template>
