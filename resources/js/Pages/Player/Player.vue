<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <!--Поиск треков в плейлисте исполнителя-->
            <div class="flex">
                <div class="relative h-12">
                    <input
                        type="text"
                        placeholder="Поиск по трекам..."
                        class="search-input w-full px-3 py-2 pr-8 border rounded"
                    />
                    <!--<span class="absolute inset-y-0 end-0 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>-->
                </div>
            </div>
            
            <!--Карточка трека-->
            <div class="flex flex-col lg:flex-row gap-4 lg:h-48 space-y-4 lg:space-y-0">
                <img src="/img/tt.jpeg" alt=""
                    class="object-contain rounded-2xl h-full"
                >

                <div class="flex flex-col h-full justify-between">
                    <div class="flex flex-wrap gap-2">
                        <span class="bg-neon-purple">ПОНРАВИЛОСЬ: 123k <i class="fa-solid fa-heart"></i></span>
                        <span class="bg-neon-green">КОММЕНТАРИЕВ: 11k <i class="fa-solid fa-comment"></i></span>
                    </div>

                    <div class="flex flex-col gap-2 mt-4 lg:mt-0">
                        <span class="extra-title">Knock knock</span>
                        <span class=""><span class="context">Сделано</span> Wolfgang Murgen</span>
                    </div>
                </div>
            </div>

            <!--Теги-->
            <div class="flex flex-wrap gap-1 w-98">
                 <span class="tag">#Вайб 2022</span>
                 <span class="tag">#Тренды 2023</span>
                 <span class="tag">#Отдых</span>
                 <span class="tag">#52</span>
            </div>
        </div>
    </AppLayout>
</template>