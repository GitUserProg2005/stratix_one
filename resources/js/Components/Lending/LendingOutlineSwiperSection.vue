<script setup>
import Carousel from '@/Components/Carousel.vue';

const slides = [
    {
        title: 'Архитектура',
        text: 'Модульная структура и готовые UI-паттерны для быстрой сборки экранов.',
    },
    {
        title: 'Темы',
        text: 'Светлая и тёмная тема с общими токенами — без расхождений в компонентах.',
    },
    {
        title: 'Формы и карточки',
        text: 'Инпуты, теги, outline-блоки и стекло — единый визуальный язык.',
    },
    {
        title: 'Деплой',
        text: 'SSR, ассеты и PWA — шаблон заточен под продакшен-сценарии.',
    },
];

/** Мобилка: 1 слайд; десктоп (lg+): 2 слайда */
const breakpoints = {
    1024: {
        slidesPerView: 2,
        spaceBetween: 20,
    },
};
</script>

<template>
    <section class="mt-16 space-y-6 lending-outline-reel">
        <div class="flex flex-col items-center text-center px-1">
            <p class="context mb-2">Листайте карточки — каждая в стиле content-outline</p>
            <h2 class="over-title">Обзор возможностей</h2>
        </div>

        <Carousel
            spaced-layout
            :items="slides"
            :slides-per-view="1"
            :space-between="16"
            :breakpoints="breakpoints"
            :autoplay="false"
        >
            <template #default="{ item }">
                <div class="content-outline flex flex-col min-h-[200px] h-full">
                    <h3 class="title">{{ item.title }}</h3>
                    <p class="context mt-3 flex-1">{{ item.text }}</p>
                    <button type="button" class="primary-btn mt-4 self-start">
                        Подробнее
                    </button>
                </div>
            </template>
        </Carousel>
    </section>
</template>

<style scoped>
.lending-outline-reel :deep(.swiper-slide) {
    height: auto;
}

.lending-outline-reel :deep(.swiper-wrapper) {
    align-items: stretch;
}
</style>
