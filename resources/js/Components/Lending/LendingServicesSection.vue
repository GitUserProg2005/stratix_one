<template>
    <section class="mt-16 space-y-6">
        <div class="flex flex-col items-center ">
            <p class="context mb-2">Поверь, у нас есть много всего!</p>
            <h2 class="over-title">Дополнительные услуги</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 min-h-[300px] items-stretch">
            <div class="content-dark h-full space-y-4">
                <h2 class="title-on-dark">"Быстрая оптимизация решений, отличная работа!"</h2>
                <p class="mt-4">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
            </div>

            <div class="content-ice flex flex-col justify-between relative overflow-hidden h-full min-h-[280px]">
                <div>
                    <h2 class="title-on-dark">"Команда собрала уникальные решения!"</h2>
                    <p class="mt-4">Сделайте свой проект уникальным с помощью готовых компонентов!</p>
                </div>

                <div class="flex items-center gap-2">
                    <img
                        src="/img/abstract/cube_noise.png"
                        class="w-12 h-12 rounded-full object-cover"
                        alt=""
                    >
                    <div class="flex flex-col">
                        <p>Александр Бойкотенко</p>
                        <p class="context">CEO, «Сервисы Дайнамикс»</p>
                    </div>
                </div>

                <img
                    class="absolute -bottom-28 -right-28 w-56 object-contain pointer-events-none"
                    src="/img/abstract/dawn.png"
                    alt=""
                >
            </div>
        </div>
    </section>
</template>
