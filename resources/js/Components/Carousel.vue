<script setup>
import { computed } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  autoplay: {
    type: Boolean,
    default: true,
  },
  navigation: {
    type: Boolean,
    default: true,
  },
  pagination: {
    type: [Boolean, Object],
    default: true,
  },
  slidesPerView: {
    type: Number,
    default: 1,
  },
  spaceBetween: {
    type: Number,
    default: 14,
  },
});

const modules = [Navigation, Pagination, Autoplay];

const autoplayOptions = computed(() => {
  if (!props.autoplay) return false;
  return { delay: 3500, disableOnInteraction: false, pauseOnMouseEnter: true };
});

const paginationOptions = computed(() => {
  if (props.pagination === false) return false;
  if (props.pagination === true) return { clickable: true };
  return props.pagination;
});
</script>

<template>
  <Swiper
    class="ui-carousel"
    :modules="modules"
    :slides-per-view="slidesPerView"
    :space-between="spaceBetween"
    :navigation="navigation"
    :pagination="paginationOptions"
    :autoplay="autoplayOptions"
  >
    <SwiperSlide v-for="(item, idx) in items" :key="idx">
      <slot :item="item" :index="idx" />
    </SwiperSlide>
  </Swiper>
</template>

<style>
.ui-carousel {
  --swiper-navigation-color: #111827;
  --swiper-pagination-color: #111827;
}

.ui-carousel .swiper-button-prev,
.ui-carousel .swiper-button-next {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.6);
}

.ui-carousel .swiper-button-prev:after,
.ui-carousel .swiper-button-next:after {
  font-size: 14px;
  font-weight: 700;
}

.ui-carousel .swiper-pagination-bullet {
  background: rgba(17, 24, 39, 0.35);
  opacity: 1;
}

.ui-carousel .swiper-pagination-bullet-active {
  background: rgba(17, 24, 39, 0.9);
}
</style>

