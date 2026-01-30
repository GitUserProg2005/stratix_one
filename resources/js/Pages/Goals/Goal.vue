<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
  <AppLayout>
    <div class="space-y-8">
        <section class="grid grid-cols1 lg:grid-cols-2 
            items-stretch
            gap-4"
            mb-4
        >
            <div class="flex flex-col gap-6">
                <div class="content relative">

                    <span class="context">Пока что нет задач</span>

                    <div class="absolute left-4 -bottom-4 bg-neon-red p-1">
                        Создал...
                    </div>
                </div>
                <div class="content relative">
                    <span class="context">Пока что нет задач</span>

                    <div class="absolute left-4 -bottom-4 bg-neon-yellow p-1">
                        Делаю...
                    </div>
                </div>

                <div class="content relative">
                    <span class="context">Пока что нет задач</span>

                    <div class="absolute left-4 -bottom-4 bg-neon-green p-1">
                        Сделал...
                    </div>
                </div>
            </div>

            <img 
                class="w-full h-full rounded-2xl object-cover"
                src="https://4kwallpapers.com/images/wallpapers/macos-big-sur-apple-layers-fluidic-colorful-wwdc-stock-3440x1440-1455.jpg" alt=""
            >
        </section>

        <h2 class="extra-title">Подобрать калорийность</h2>

        <div class="content space-y-4">
            <div>
                <h2>Всего задач:</h2>

                <div class="flex items-center justify-between">
                    <div class="relative w-64 h-3 bg-body rounded-full">
                        <div class="left-0 absolute w-[50%] h-3 rounded-full bg-[#fd3f9a]">
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="font-medium">5 шагов</span>

                        <span class="icon-btn flex items-center justify-center">
                            <i class="fa-solid fa-fire-flame-curved"></i>
                        </span>
                    </div>
                </div>
            </div>

            <ul class="space-y-4 mt-2">
                <h2 class="title">Подзадачи:</h2>

                <li class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        class="accent-[#7111EE] w-5 h-5 rounded-lg"
                    />

                    <span>Рассмотреть базу</span>
                </li>
                
                <li class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        class="accent-[#7111EE] w-5 h-5 rounded-lg"
                    />

                    <span>Рассмотреть базу</span>
                </li>

                <li class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        class="accent-[#7111EE] w-5 h-5 rounded-lg"
                    />

                    <span>Рассмотреть базу</span>
                </li>
            </ul>
        </div>
    </div>
  </AppLayout>
</template>