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
    <section class="flex flex-col items-center justify-start pb-10 md:pb-14 pt-1 md:pt-2">
        <div class="mx-auto max-w-3xl px-4 text-center space-y-5 flex flex-col items-center justify-center">
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
                    Настроить автоматизацию сейчас
                </button>
            </div>
        </div>
    </section>
</template>
