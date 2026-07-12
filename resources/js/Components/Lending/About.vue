<script setup>
const features = [
    { icon: 'fa-comment-dots', text: 'PLAN-режим' },
    { icon: 'fa-sliders', text: 'ASK-режим' },
    { icon: 'fa-brain', text: 'AGENT-режим' },
];
</script>

<template>
    <section class="mt-16">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 items-center">
            <div>
                <h2 class="title">Автоматизируйте свои процессы в несколько кликов</h2>

                <p class="context mt-4">
                    Наш AI-агент позволяет писать и редактировать
                    сценарии на основе ваших запросов
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mt-8">
                    <div
                        v-for="feature in features"
                        :key="feature.text"
                        class="bg-content-glass rounded-2xl flex flex-col items-center justify-center text-center gap-3 !p-4 min-h-[1.5rem]"
                    >
                        <p class="t-body text-sm leading-snug">{{ feature.text }}</p>
                    </div>
                </div>

                <p class="title-2 mt-8">
                    Это как иметь в команде опытного эксперта по автоматизации — 24/7, прямо в чате.
                </p>

                <button type="button" class="primary-btn mt-6">
                    Попробовать бесплатно
                </button>
            </div>

            <div>
                <img src="/img/lending/about.png" class="w-full object-contain rounded-2xl" alt="AI-ассистент для автоматизации">
            </div>
        </div>
    </section>
</template>
