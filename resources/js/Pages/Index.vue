<script setup>
import { computed } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { FreeMode } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/free-mode';

import AppLayout from '@/Layouts/AppLayout.vue';
import TrackSearch from '@/Components/Search/Instances/TrackSearch.vue';
import TryReels from '@/Components/TryReels.vue';
import { fetchTrack } from '@/utils/useFetchTrack';

const props = defineProps({
  tracks: {
    type: Object,
    default: () => ({
      recommended: { data: [] },
      recently: { data: [] },
      by_tag: {},
      tag_names: {},
    }),
  },
});

const modules = [FreeMode];

const recommendedList = computed(() => props.tracks?.recommended?.data ?? []);
const recentlyList = computed(() => props.tracks?.recently?.data ?? []);
const byTagEntries = computed(() => {
  const byTag = props.tracks?.by_tag ?? {};
  const names = props.tracks?.tag_names ?? {};
  return Object.entries(byTag).map(([tagId, col]) => ({
    tagId,
    tagName: names[tagId] ?? `Подборка ${tagId}`,
    list: col?.data ?? [],
  })).filter(entry => entry.list.length > 0);
});

function openTrack(trackId) {
  fetchTrack(trackId, { back: route('tracks.index') });
}
</script>

<template>
  <AppLayout>
    <div class="p-4 pb-24 space-y-10">
      <TrackSearch class="mb-6" />

      <!-- Вы недавно слушали -->
      <section v-if="recentlyList.length" class="space-y-4">
        <h2 class="text-2xl font-bold tracking-tight">Вы недавно слушали</h2>
        <Swiper
          :modules="modules"
          :slides-per-view="'auto'"
          :space-between="12"
          free-mode
          class="!overflow-visible"
        >
          <SwiperSlide
            v-for="track in recentlyList"
            :key="track.id"
            class="!w-[180px] sm:!w-[200px] flex-shrink-0"
          >
            <button
              type="button"
              class="w-full text-left p-3 rounded-xl bg-[#181818] hover:bg-[#282828] transition-colors group"
              @click="openTrack(track.id)"
            >
              <div class="relative mb-3 aspect-square rounded-lg overflow-hidden bg-[#333] shadow-lg group-hover:scale-[1.02] transition-transform">
                <img
                  v-if="track.preview_url"
                  :src="track.preview_url"
                  :alt="track.title"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
                  <i class="fa-solid fa-music text-3xl"></i>
                </div>
              </div>
              <div class="min-w-0">
                <p class="font-semibold truncate">{{ track.title }}</p>
                <p class="text-sm text-gray-400 truncate">{{ track.artist?.name ?? '' }}</p>
              </div>
            </button>
          </SwiperSlide>
        </Swiper>
      </section>

      <!-- Рекомендуем -->
      <section v-if="recommendedList.length" class="space-y-4">
        <h2 class="text-2xl font-bold tracking-tight">Вам может понравиться</h2>
        <Swiper
          :modules="modules"
          :slides-per-view="'auto'"
          :space-between="12"
          free-mode
          class="!overflow-visible"
        >
          <SwiperSlide
            v-for="track in recommendedList"
            :key="track.id"
            class="!w-[180px] sm:!w-[200px] flex-shrink-0"
          >
            <button
              type="button"
              class="w-full text-left p-3 rounded-xl bg-[#181818] hover:bg-[#282828] transition-colors group"
              @click="openTrack(track.id)"
            >
              <div class="relative mb-3 aspect-square rounded-lg overflow-hidden bg-[#333] shadow-lg group-hover:scale-[1.02] transition-transform">
                <img
                  v-if="track.preview_url"
                  :src="track.preview_url"
                  :alt="track.title"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
                  <i class="fa-solid fa-music text-3xl"></i>
                </div>
              </div>
              <div class="min-w-0">
                <p class="font-semibold truncate">{{ track.title }}</p>
                <p class="text-sm text-gray-400 truncate">{{ track.artist?.name ?? '' }}</p>
              </div>
            </button>
          </SwiperSlide>
        </Swiper>
      </section>

      <!-- По тегам -->
      <section
        v-for="{ tagId, tagName, list } in byTagEntries"
        :key="tagId"
        class="space-y-4"
      >
        <h2 class="text-2xl font-bold tracking-tight">{{ tagName }}</h2>
        <Swiper
          :modules="modules"
          :slides-per-view="'auto'"
          :space-between="12"
          free-mode
          class="!overflow-visible"
        >
          <SwiperSlide
            v-for="track in list"
            :key="track.id"
            class="!w-[180px] sm:!w-[200px] flex-shrink-0"
          >
            <button
              type="button"
              class="w-full text-left p-3 rounded-xl bg-[#181818] hover:bg-[#282828] transition-colors group"
              @click="openTrack(track.id)"
            >
              <div class="relative mb-3 aspect-square rounded-lg overflow-hidden bg-[#333] shadow-lg group-hover:scale-[1.02] transition-transform">
                <img
                  v-if="track.preview_url"
                  :src="track.preview_url"
                  :alt="track.title"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
                  <i class="fa-solid fa-music text-3xl"></i>
                </div>
              </div>
              <div class="min-w-0">
                <p class="font-semibold truncate">{{ track.title }}</p>
                <p class="text-sm text-gray-400 truncate">{{ track.artist?.name ?? '' }}</p>
              </div>
            </button>
          </SwiperSlide>
        </Swiper>
      </section>

      <!-- Пустое состояние -->
      <div
        v-if="recentlyList.length === 0 && recommendedList.length === 0 && byTagEntries.length === 0"
        class="flex flex-col items-center justify-center py-20 text-gray-400 text-center"
      >
        <i class="fa-solid fa-music text-6xl mb-4 opacity-50"></i>
        <p class="text-lg">Пока нет подборок</p>
        <p class="text-sm mt-1">Слушайте треки — здесь появятся рекомендации и недавние прослушивания</p>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
:deep(.swiper) {
  overflow: visible;
}
:deep(.swiper-wrapper) {
  align-items: stretch;
}
:deep(.swiper-slide) {
  height: auto;
}
</style>
