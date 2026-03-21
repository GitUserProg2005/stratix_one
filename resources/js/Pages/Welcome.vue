<script setup>
import LendingLayout from '@/Layouts/LendingLayout.vue';
</script>

<template>
    <div class="relative bg-body">
        <!-- Right-side background (covers header + hero) -->
        <div
            class="pointer-events-none absolute top-0 right-0 
            w-full md:w-1/2 h-[520px] md:h-[720px] blur-sm md:blur-none"
            style="
                background-image: url('https://avatars.mds.yandex.net/get-bunker/120922/9b02a86d3f74c34caef53bfd11dd07e68b8866f9/orig');
                background-repeat: no-repeat;
                background-position: right top;
                background-size: cover;
            "
        />

        <!-- Soft fade into white (Yandex-like) -->
        <div
            class="pointer-events-none absolute top-0 right-0 w-full md:w-1/2 h-[520px] md:h-[720px]"
            style="background: linear-gradient(90deg, rgba(255,255,255,1) 0%, rgba(255,255,255,0.85) 25%, rgba(255,255,255,0) 55%);"
        />

        <!--Main content-->
        <LendingLayout>
            <!--Hero content-->
            <section class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center min-h-[420px]">
                <div class="space-y-4">
                    <h2 class="over-title">Простой старт для MVP</h2>
                    <p class="context max-w-prose">
                        Светлая минималистичная тема и базовые компоненты. Картинка справа работает как фон и для шапки, и для hero — как в Yandex 360.
                    </p>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button class="primary-btn">Начать</button>
                        <button class="tag">Посмотреть UI kit</button>
                    </div>
                </div>

                <!-- Right column intentionally empty: background lives behind it -->
                <div class="hidden md:block" />
            </section>

            <!-- Components -->
            <section class="content mt-16">
                <div class="flex gap-6">
                    <div>
                        <img
                            class="w-32 rounded-full object-cover"
                            src="/img/abstract/cube_noise.png" alt="">
                    </div>
                    <div class="flex flex-col items-start">
                        <h2 class="title">Компоненты</h2>

                        <p class="pt-1 text-gray-500">Сделайте свой проект уникальным с помощью готовых компонентов!</p>

                        <button class="primary-btn-white mt-4">Посмотреть UI kit</button>
                    </div>
                </div>
            </section>

            <section class="mt-16 space-y-6">
                <div class="flex flex-col items-center ">
                    <p class="context mb-2">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                    <h2 class="over-title">Потенциальные клиенты</h2>
                </div>

                <div class="grid lg:grid-cols-4 md:grid-cols-2 grid-cols-1 gap-6">
                    <div class="content-corporate-blue animate-lift space-y-4 relative overflow-hidden">
                        <h3 class="title">Компоненты</h3>
                        <p class="context">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                        <button class="primary-btn-white"><i class="fa-solid fa-angle-right"></i></button>
                        <img class="absolute bottom-5 -right-8 w-32 -rotate-45" src="/img/abstract/wave1.png" alt="">
                    </div>

                    <div class="content-corporate-blue animate-lift space-y-4">
                        <h3 class="title">Фреймворки</h3>
                        <p class="context">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                        <button class="primary-btn-white"><i class="fa-solid fa-angle-right"></i></button>
                    </div>

                    <div class="content-corporate-blue animate-lift space-y-4">
                        <h3 class="title">Фреймворки</h3>
                        <p class="context">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                        <button class="primary-btn-white"><i class="fa-solid fa-angle-right"></i></button>
                    </div>

                    <div class="content-corporate-blue animate-lift space-y-4">
                        <h3 class="title">Фреймворки</h3>
                        <p class="context">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                        <button class="primary-btn-white"><i class="fa-solid fa-angle-right"></i></button>
                    </div>
                </div>

                <div class="w-full grid lg:grid-cols-5 md:grid-cols-3 grid-cols-2 gap-6">
                    <div class="content-corporate-blue animate-lift space-y-4">
                        <h3 class="title">Компоненты</h3>
                        <p class="context">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                        <button class="primary-btn-white"><i class="fa-solid fa-angle-right"></i></button>
                        <img src="" alt="">
                    </div>

                    <div class="content-corporate-blue animate-lift space-y-4">
                        <h3 class="title">Фреймворки</h3>
                        <p class="context">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                        <button class="primary-btn-white"><i class="fa-solid fa-angle-right"></i></button>
                    </div>

                    <div class="content-corporate-blue animate-lift space-y-4">
                        <h3 class="title">Фреймворки</h3>
                        <p class="context">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                        <button class="primary-btn-white"><i class="fa-solid fa-angle-right"></i></button>
                    </div>

                    <div class="content-corporate-blue animate-lift space-y-4">
                        <h3 class="title">Фреймворки</h3>
                        <p class="context">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                        <button class="primary-btn-white"><i class="fa-solid fa-angle-right"></i></button>
                    </div>

                    <div class="content-corporate-blue animate-lift space-y-4">
                        <h3 class="title">Фреймворки</h3>
                        <p class="context">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                        <button class="primary-btn-white"><i class="fa-solid fa-angle-right"></i></button>
                    </div>
                </div>
            </section>

            <section class="mt-16 space-y-6">
                <div class="flex flex-col items-center ">
                    <p class="context mb-2">Что еще? Много всего!</p>
                    <h2 class="over-title">Моментальный старт</h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
                    <!-- Column 1 -->
                    <div class="flex flex-col h-full">
                        <div class="content-dark relative overflow-hidden flex flex-col h-full">
                            <h2 class="title">Простой старт для MVP</h2>
                            <p class="mt-4">Сделайте свой проект уникальным с помощью готовых компонентов!</p>

                            <img
                                class="absolute -bottom-12 -right-12 w-1/2 object-contain pointer-events-none"
                                src="/img/abstract/capsule_noise.png"
                                alt=""
                            />
                        </div>
                    </div>

                    <!-- Column 2 -->
                    <div class="flex flex-col h-full gap-6">
                        <div class="content-outline relative overflow-hidden flex flex-col flex-1">
                            <h2 class="title">Простой старт для MVP</h2>
                            <p class="context mt-4">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                        </div>

                        <div class="content-outline relative overflow-hidden flex flex-col flex-1">
                            <h2 class="title">Простой старт для MVP</h2>
                            <p class="context mt-4">Сделайте свой проект уникальным с помощью готовых компонентов!</p>

                            <img
                                class="absolute -bottom-48 -right-20 w-1/2 object-contain pointer-events-none"
                                src="/img/abstract/circle.png"
                                alt=""
                            />
                        </div>
                    </div>

                    <!-- Column 3 -->
                    <div class="flex flex-col h-full">
                        <div class="content-dark relative overflow-hidden flex flex-col h-full">
                            <h2 class="title">S3-интеграция</h2>
                            <p class="mt-4">Отличные технические решения для вашего проекта</p>

                            <img
                                class="absolute -bottom-12 -right-12 w-1/2 object-contain pointer-events-none"
                                src="/img/abstract/conus_noise.png"
                                alt=""
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-16 space-y-6">
                <div 
                class="relative overflow-hidden rounded-3xl p-10 min-h-[520px] flex items-center">
                    <!-- Blurred photo background -->
                    <div
                        class="absolute inset-0 bg-cover bg-center blur-md scale-110"
                        style="background-image: url('https://www.profguide.io/images/article/a/52/3avHm1YTYs.webp');"
                        aria-hidden="true"
                    />
                    <img
                        class="absolute right-0 top-1/2 -translate-y-1/2 h-full w-1/2 object-contain pointer-events-none"
                        src="/img/abstract/cube_noise.png"
                        alt=""
                    />

                    <div class="relative z-10 w-full lg:w-1/2 pr-4">
                        <h2 class="title-font text-white">
                            Яндекс 360 для бизнеса
                        </h2>
                        <p class="text-white mt-4 max-w-prose">
                            Наши сервисы и тарифы для крупного, среднего и малого бизнеса
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3 items-center">
                            <button class="primary-btn">Подключить</button>
                            <button class="primary-btn-white">Все тарифы</button>
                        </div>
                    </div>
                </div>
            </section>
        </LendingLayout>
    </div>
</template>