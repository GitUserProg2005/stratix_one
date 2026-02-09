<script setup>
import { ref, reactive, watch, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Mousewheel, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/mousewheel';

import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import VinylDisc from './VinylDisc.vue';
import Like from './Like.vue';
import Search from '@/Components/Search.vue';
import Back from '../Player/Back.vue';

const props = defineProps({
  snippets: {
    type: Array,
    default: () => [],
  },
});

const modules = [Mousewheel, Pagination];
const audioRef = ref(null);
const currentIndex = ref(0);
const swiperRef = ref(null);
const isPlaying = ref(false);
const heartRefs = reactive({});
const slideRef = ref(null);
let tapTimeout = null;

function setHeartRef(id, el) {
  if (el) {
    heartRefs[id] = el;
  }
}

function onSlideTap(event, id) {
  if (tapTimeout) {
    clearTimeout(tapTimeout);
    tapTimeout = null;

    console.log('NO TAP HEART');

    return;
  }

  tapTimeout = setTimeout(() => {
    togglePlay();
    tapTimeout = null;
  }, 250);
}

function playSnippet(index) {
  if (!props.snippets[index] || !audioRef.value) return;

  const snippet = props.snippets[index];
  const audioEl = audioRef.value;

  audioEl.src = snippet.audio_url;
  audioEl.play().catch(() => {});
  isPlaying.value = true;
}

function togglePlay() {
  if (!audioRef.value) return;

  if (audioRef.value.paused) {
    audioRef.value.play().catch(() => {});
    isPlaying.value = true;
  } else {
    audioRef.value.pause();
    isPlaying.value = false;
  }
}

function onSlideChange(swiper) {
  currentIndex.value = swiper.activeIndex;
  playSnippet(currentIndex.value);
}

function onSwiper(swiper) {
  swiperRef.value = swiper;
}

function onBack() {
  window.history.back();
}

watch(() => props.snippets, (list) => {
  if (list?.length) {
    playSnippet(0);
  }
}, { immediate: true });

onBeforeUnmount(() => {
  if (audioRef.value) {
    audioRef.value.pause();
    audioRef.value.src = '';
  }
});
</script>

<template>
  <AppLayout>
    <div class="w-full min-h-[calc(100vh-44px)] 
      flex justify-center pt-4"
    >
      <div
        class="
          w-full
          max-w-[420px]
          sm:max-w-[480px]
          md:max-w-[540px]
          h-[calc(100vh-64px)]
          relative
        "
      >
      <div class="z-50 w-full absolute top-0 left-0 right-0 p-4">
        <div class="flex items-center gap-2">
          <Back @back="onBack" backUrl="tracks.index" />
          <Search />
        </div>
      </div>

        <Swiper
          :modules="modules"
          direction="vertical"
          :slides-per-view="1"
          :mousewheel="true"
          @swiper="onSwiper"
          @slide-change="onSlideChange"
          class="h-full"
        >
          <SwiperSlide 
            v-for="snippet in snippets" 
            :key="snippet.id"
            class="h-full"
          >
              <div class="relative h-full reel-slide
                p-4 overflow-hidden rounded-2xl"
                ref="slideRef"
                @click="togglePlay"
              >
                <div
                  class="
                    absolute inset-0
                    bg-black
                    flex items-center justify-center
                  "
                >
                  <div class="absolute inset-0
                    overflow-hidden">
                    <img
                      v-if="snippet.track?.preview_url"
                      :src="snippet.track.preview_url"
                      class="w-full h-full object-cover scale-110 block"
                    />
                    <i
                      v-else
                      class="fa-solid fa-music text-white/40 text-6xl"
                    ></i>
                    <div class="absolute inset-0 bg-black/30 backdrop-blur-xl"></div>
                  </div>
                </div>

                <div class="relative z-40 h-full">
                  <div class="h-full flex flex-1 justify-center items-center">
                    <VinylDisc :src="snippet.track.preview_url" :is-playing="isPlaying" />

                    <Transition class="fade-scale"> 
                      <span class="z-20 w-10 bg-green-500
                        h-10 flex items-center justify-center 
                        rounded-full absolute"
                        v-if="!isPlaying"
                      >
                        <i class="fa-solid fa-play"></i>
                      </span>
                    </Transition>
                  </div>

                  <div class="absolute right-1 bottom-1/3 
                    flex flex-col items-center gap-6"
                  >
                    <button class="flex flex-col items-center gap-1">
                      <Avatar name="Wix" />
                    </button>

                    <Like
                      :snippet-id="snippet.id"
                      :initial-liked="snippet.is_liked"
                      :initial-likes-count="snippet.likes_count"
                    />

                    <!--<button class="flex flex-col items-center gap-1">
                      <i class="fa-regular fa-heart text-2xl"></i>
                      <span class="text-xs">11 тыс.</span>
                    </button>-->
                    <button class="flex flex-col items-center gap-1">
                      <i class="fa-solid fa-comment"></i>
                      <span class="text-xs">2 тыс.</span>
                    </button>
                    <button class="flex flex-col items-center gap-1">
                      <i class="fa-solid fa-reply"></i>
                      <span class="text-xs">577</span>
                    </button>
                  </div>

                  <div class="absolute lg:left-4 bottom-4 lg:bottom-2 flex flex-col gap-1">
                    <div class="flex flex-row gap-2 context">
                      <span>$Waxho</span>
                      <span class="w-2 h-2 rounded-full bg-green-500 mt-1"></span>
                      <span>2025-12-01</span>
                    </div> 

                    <span>
                      Название: {{ snippet.track.title }}
                    </span> 

                    <span class="context">
                      #52 #my #wish #vibe 
                    </span>
                  </div>
                </div>
              </div>
          </SwiperSlide>
        </Swiper>
      </div>
    </div>

    <audio ref="audioRef" loop class="hidden" 
      crossorigin="anonymous" 
    />
  </AppLayout>
</template>

<style scoped>
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.8);
}

.fade-scale-enter-to,
.fade-scale-leave-from {
  opacity: 1;
  transform: scale(1);
}
</style>