<script setup>
import { ref, reactive, watch, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Mousewheel, Pagination } from 'swiper/modules';
import axios from 'axios';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/mousewheel';

import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import VinylDisc from './VinylDisc.vue';
import Like from './Like.vue';
import Comments from './Comments.vue';
import SnippetSearch from '@/Components/Search/Instances/SnippetSearch.vue';
import Back from '../Player/Back.vue';
import { stopListen } from '@/utils/stopListen';

const props = defineProps({
  snippets: {
    type: Array,
    default: () => [],
  },
});

const modules = [Mousewheel, Pagination];

// Основной массив для Swiper
const snippetsList = ref([...props.snippets]);
const isLoadingMore = ref(false);

const audioRef = ref(null);
const currentIndex = ref(0);
const swiperRef = ref(null);
const isPlaying = ref(false);
const currentSnippetId = ref(null);

const heartRefs = reactive({});
const slideRef = ref(null);
let tapTimeout = null;

function setHeartRef(id, el) {
  if (el) heartRefs[id] = el;
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

function playSnippet(index) {
  const snippet = snippetsList.value[index];
  const audioEl = audioRef.value;

  if (!snippet || !audioEl) return;

  // Если переключаемся на другой сниппет
  if (currentSnippetId.value && currentSnippetId.value !== snippet.id) {
    const dur = audioEl.duration;
    if (Number.isFinite(dur)) {
      stopListen('back', audioEl.currentTime, dur, 'snippet', currentSnippetId.value);
    }
  }

  currentSnippetId.value = snippet.id;
  audioEl.src = snippet.audio_url;

  audioEl.onended = () => {
    const dur = audioEl.duration;
    if (Number.isFinite(dur)) {
      stopListen('ended', dur, dur, 'snippet', currentSnippetId.value);
    }
    // ручной loop
    audioEl.currentTime = 0;
    audioEl.play().catch(() => {});
  };

  audioEl.play().catch(() => {});
  isPlaying.value = true;
}

async function loadMoreSnippets() {
  if (isLoadingMore.value || snippetsList.value.length === 0) return;

  isLoadingMore.value = true;
  const lastId = snippetsList.value.at(-1).id;

  try {
    const { data } = await axios.get(route('reels.get'), { params: { lastSnippetId: lastId } });
    if (data.snippets.length) { 
      snippetsList.value.push(...data.snippets);
      console.log(data.snippets);
    }
  } catch (e) {
    console.error('Ошибка подгрузки рилзов', e);
  } finally {
    isLoadingMore.value = false;
  }
}

function onSlideChange(swiper) {
  currentIndex.value = swiper.activeIndex;
  playSnippet(currentIndex.value);

  // Если дошли до последнего слайда — подгружаем новые
  if (currentIndex.value === snippetsList.value.length - 1) loadMoreSnippets();
}

function onSwiper(swiper) {
  swiperRef.value = swiper;
}

function onBack() {
  router.visit(route('tracks.index'));
}

// стартуем первый сниппет сразу
watch(snippetsList, (list) => {
  if (list?.length && !currentSnippetId.value) playSnippet(0);
}, { immediate: true });

onBeforeUnmount(() => {
  const audioEl = audioRef.value;
  if (audioEl && currentSnippetId.value) {
    const dur = audioEl.duration;
    if (Number.isFinite(dur)) {
      stopListen('back', audioEl.currentTime, dur, 'snippet', currentSnippetId.value);
    }
    audioEl.pause();
    audioEl.src = '';
  }
});
</script>

<template>
  <AppLayout>
    <div class="w-full min-h-[calc(100vh-44px)] flex justify-center pt-4">
      <div class="w-full max-w-[420px] sm:max-w-[480px] md:max-w-[540px] h-[calc(100vh-64px)] relative">
        <!-- шапка -->
        <div class="z-50 w-full absolute top-0 left-0 right-0 p-4 flex items-center gap-2">
          <Back @back="onBack" backUrl="tracks.index" />
          <SnippetSearch />
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
          <SwiperSlide v-for="snippet in snippetsList" :key="snippet.id" class="h-full">
            <div class="relative h-full reel-slide p-4 overflow-hidden rounded-2xl" ref="slideRef" @click="togglePlay">
              <!-- фон -->
              <div class="absolute inset-0 bg-black flex items-center justify-center">
                <div class="absolute inset-0 overflow-hidden">
                  <img v-if="snippet.track?.preview_url" :src="snippet.track.preview_url" class="w-full h-full object-cover scale-110 block" />
                  <i v-else class="fa-solid fa-music text-white/40 text-6xl"></i>
                  <div class="absolute inset-0 bg-black/30 backdrop-blur-xl"></div>
                </div>
              </div>

              <!-- контент -->
              <div class="relative z-40 h-full flex flex-col justify-between">
                <div class="h-full flex justify-center items-center">
                  <VinylDisc :src="snippet.track.preview_url" :is-playing="isPlaying" />
                  <Transition class="fade-scale">
                    <span class="z-20 w-10 h-10 bg-green-500 flex items-center justify-center rounded-full absolute" v-if="!isPlaying">
                      <i class="fa-solid fa-play"></i>
                    </span>
                  </Transition>
                </div>

                <div class="absolute right-1 bottom-1/3 flex flex-col items-center gap-6">
                  <button class="flex flex-col items-center gap-1"><Avatar name="Wix" /></button>
                  <Like :snippet-id="snippet.id" :initial-liked="snippet.is_liked" :initial-likes-count="snippet.likes_count" />
                  <Comments />
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
                  <span>Название: {{ snippet.track.title }}</span>
                  <span class="context">#52 #my #wish #vibe</span>
                </div>
              </div>
            </div>
          </SwiperSlide>
        </Swiper>
      </div>
    </div>

    <audio ref="audioRef" class="hidden" crossorigin="anonymous" />
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
