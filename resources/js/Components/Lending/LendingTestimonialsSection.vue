<script setup>
import Carousel from '@/Components/Carousel.vue';

const avatars = {
    diana: 'https://cursor.com/marketing-static/_next/image?url=https%3A%2F%2Fptht05hbb1ssoooe.public.blob.vercel-storage.com%2Fassets%2Favatars%2Fdiana-hu-avatar.png&w=96&q=70',
    misc: 'https://cursor.com/marketing-static/_next/image?url=https%3A%2F%2Fptht05hbb1ssoooe.public.blob.vercel-storage.com%2Fassets%2Fmisc%2Fasset-b7d40eac35d910f3448b.png&w=96&q=70',
};

const testimonials = [
    {
        quote: 'За неделю перенесли три процесса в сценарии — команда перестала тонуть в ручных задачах. Запуск webhook занял буквально один вечер.',
        name: 'Алексей Морозов',
        role: 'CTO, SaaS-стартап',
        avatar: avatars.diana,
        rating: 5,
    },
    {
        quote: 'AI-агент собрал первый workflow быстрее, чем мы успели описать ТЗ. Теперь правим сценарии прямо в чате — без постоянных созвонов с разработкой.',
        name: 'Марина Ковалева',
        role: 'Product Lead, e-commerce',
        avatar: avatars.misc,
        rating: 5,
    },
    {
        quote: 'Интеграция через callback закрыла наш кейс с CRM: данные уходят в продукт, ошибки видны сразу. Для MVP это спасло несколько недель.',
        name: 'Игорь Власов',
        role: 'Founder, финтех',
        avatar: avatars.diana,
        rating: 5,
    },
    {
        quote: 'Cron + webhook покрыли все наши триггеры. Команда поддержки наконец перестала вручную дублировать одни и те же действия каждый день.',
        name: 'Елена Сафронова',
        role: 'Head of Operations',
        avatar: avatars.misc,
        rating: 5,
    },
];

const breakpoints = {
    768: {
        slidesPerView: 1,
        spaceBetween: 20,
    },
    1024: {
        slidesPerView: 2,
        spaceBetween: 24,
    },
};
</script>

<template>
    <section class="mt-16 space-y-6 lending-testimonials">
        <div class="flex flex-col items-center text-center px-1">
            <p class="context mb-2">Что говорят команды после запуска</p>
            <h2 class="title">Отзывы клиентов</h2>
        </div>

        <Carousel
            spaced-layout
            :items="testimonials"
            :slides-per-view="1"
            :space-between="16"
            :breakpoints="breakpoints"
            :navigation="false"
        >
            <template #default="{ item }">
                <article class="content-glass flex flex-col justify-between min-h-[17rem] h-full">
                    <blockquote class="title-2-on-dark text-lg sm:text-xl leading-relaxed text-balance">
                        «{{ item.quote }}»
                    </blockquote>

                    <footer class="mt-8 flex items-center gap-4">
                        <img
                            :src="item.avatar"
                            :alt="item.name"
                            class="h-12 w-12 shrink-0 rounded-full object-cover"
                            loading="lazy"
                            referrerpolicy="no-referrer"
                        >

                        <div>
                            <div class="mb-1 flex items-center gap-0.5" aria-label="Оценка 5 из 5">
                                <i
                                    v-for="star in item.rating"
                                    :key="star"
                                    class="fa-solid fa-star text-xs text-[#e97358]"
                                    aria-hidden="true"
                                />
                            </div>
                            <p class="font-semibold text-white">{{ item.name }}</p>
                            <p class="t-small text-white/60">{{ item.role }}</p>
                        </div>
                    </footer>
                </article>
            </template>
        </Carousel>
    </section>
</template>

<style scoped>
.lending-testimonials :deep(.swiper-slide) {
    height: auto;
}

.lending-testimonials :deep(.swiper-wrapper) {
    align-items: stretch;
}
</style>
