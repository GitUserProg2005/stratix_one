<script setup>
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Mousewheel, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/mousewheel';

const modules = [Mousewheel, Pagination];

// Пример массива "рилзов"
const reels = [
  { id: 1, title: 'Рилз 1', video: 'img/video.webm' },
  { id: 2, title: 'Рилз 2', video: 'img/video.webm' },
  { id: 3, title: 'Рилз 3', video: 'img/video.webm' },
];
</script>

<template>
  <Swiper
    :modules="modules"
    direction="vertical"
    :slides-per-view="1"
    :mousewheel="{ enabled: true }"
    :pagination="{ clickable: true }"
    style="height: 100vh"
  >
    <SwiperSlide v-for="reel in reels" :key="reel.id">
      123
    </SwiperSlide>
  </Swiper>
</template>
