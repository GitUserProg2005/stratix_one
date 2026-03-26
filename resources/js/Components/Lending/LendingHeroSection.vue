<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from "vue";
import { gsap } from "gsap";

const isMobile = ref(null);

const text  = 'Простой старт для MVP';
const words = text.split(' ');

const wordsRef = ref([]);

let tl = null;

const killTimeline = () => {
    if (tl) {
        tl.kill();
        tl = null;
    }
};

const checkIsMobile = () => {
    isMobile.value = window.innerWidth < 768;
};

onMounted(() => {
    checkIsMobile();
    window.addEventListener('resize', checkIsMobile);
});

onUnmounted(() => {
    killTimeline();
    window.removeEventListener('resize', checkIsMobile);
});

watch(
    isMobile,
    async (val) => {
        await nextTick();
        killTimeline();

        if (!val) {
            // DESKTOP
            tl = gsap.timeline({
                repeat: -1,
                repeatDelay: 1.5,
            });

            (wordsRef.value ?? []).forEach((el, i) => {
                if (!el) return;

                tl.to(el, {
                    y: -16,
                    duration: 0.4,
                    ease: 'power2.inOut',
                }, i * 0.2)
                    .to(el, {
                        y: 0,
                        duration: 0.4,
                        ease: 'power2.inOut',
                    }, i * 0.2 + 0.4);
            });

            return;
        }
    },
    { immediate: true },
);
</script>

<template>
    <section
        class="relative isolate flex min-h-[20rem] sm:min-h-[22rem] md:min-h-[26rem] flex-col items-center justify-start overflow-hidden pb-10 md:pb-14 pt-1 md:pt-2"
    >
        <!-- min-height нужна только чтобы % по вертикали не схлопывались; контент задаёт реальную высоту если выше -->
        <div
            class="pointer-events-none absolute inset-0 z-0 min-h-[20rem] sm:min-h-[22rem] md:min-h-[26rem] select-none"
            aria-hidden="true"
        >
            <!-- Верх: по углам, далеко друг от друга -->
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img absolute top-[2%] left-[-3%] sm:left-[1%] w-[4.5rem] sm:w-28 md:w-32 -rotate-[14deg]"
                alt=""
            >
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img lending-hero-grid-img--b absolute top-[4%] right-[-4%] sm:right-[0%] w-[5.5rem] sm:w-32 md:w-40 rotate-[20deg]"
                alt=""
            >

            <!-- Верхняя «дуга» — по бокам от центра, не в центре -->
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img lending-hero-grid-img--faint hidden sm:block absolute top-[14%] left-[12%] md:left-[16%] w-20 md:w-24 rotate-[28deg]"
                alt=""
            >
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img lending-hero-grid-img--faint hidden sm:block absolute top-[12%] right-[14%] md:right-[18%] w-[4.5rem] md:w-24 -rotate-[32deg]"
                alt=""
            >

            <!-- Середина по вертикали: лево / право, разный уровень -->
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img absolute top-[38%] left-[-2%] sm:left-[2%] w-16 sm:w-24 md:w-28 rotate-90"
                alt=""
            >
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img lending-hero-grid-img--faint absolute top-[52%] right-[4%] sm:right-[8%] w-14 sm:w-24 md:w-28 -rotate-[18deg]"
                alt=""
            >

            <!-- Центральный пояс (только md+, очень бледный — не лезет в заголовок) -->
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img lending-hero-grid-img--faint hidden md:block absolute top-[44%] left-[72%] xl:left-[78%] w-20 rotate-[48deg]"
                alt=""
            >
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img lending-hero-grid-img--faint hidden lg:block absolute top-[40%] left-[8%] w-[4.5rem] -rotate-[8deg]"
                alt=""
            >

            <!-- Низ: разнесены -->
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img lending-hero-grid-img--b absolute bottom-[6%] left-[4%] sm:left-[8%] w-28 sm:w-36 md:w-40 rotate-[10deg]"
                alt=""
            >
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img absolute bottom-[4%] right-[2%] sm:right-[6%] w-24 sm:w-28 md:w-36 -rotate-[12deg]"
                alt=""
            >
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img lending-hero-grid-img--faint hidden md:block absolute bottom-[22%] right-[10%] lg:right-[14%] w-24 rotate-[65deg]"
                alt=""
            >
            <img
                src="/img/abstract/grid.png"
                class="lending-hero-grid-img lending-hero-grid-img--faint hidden lg:block absolute bottom-[28%] left-[18%] w-20 -rotate-[5deg]"
                alt=""
            >
        </div>

        <div class="relative z-10 mx-auto max-w-3xl px-4 text-center space-y-5 flex flex-col items-center justify-center">
            <div class="label-landing">
                <span class="">Попробовать бесплатно <i class="fa-solid fa-angle-right ml-2"></i></span>
            </div>
            <div class="mt-20">
                <template v-if="!isMobile">
                    <h2>
                        <span v-for="(word, index) in words"
                            :key="index"
                            ref="wordsRef"
                            class="inline-block px-1 title-font"
                        >
                            {{ word }}
                        </span>
                    </h2>
                </template>
                <template v-else>
                    <h2 class="title-font">
                        Простой 
                        <span class="title-font">старт</span> 
                        для MVP
                    </h2>
                </template>
            </div>
            <p class="context max-w-prose mx-auto text-balance">
                Светлая минималистичная тема и базовые компоненты. Соберите интерфейс из готовых блоков и сфокусируйтесь на продукте.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <button type="button" class="primary-btn">
                    Начать
                </button>
                <button type="button" class="tag">
                    Посмотреть UI kit
                </button>
            </div>

            <div class="flex flex-wrap max-w-4xls items-center justify-center gap-2 mt-16">
                <span class="tag">Разделы маркетинга</span>
                <span class="tag">Инкубация</span>
                <span class="hidden md:block tag">Selfhosted-решения</span>
                <span class="hidden md:block tag">Коммуникация и AI-ассистенты</span>
                <span class="tag">Маршруты</span>
            </div>
        </div>
    </section>
</template>
