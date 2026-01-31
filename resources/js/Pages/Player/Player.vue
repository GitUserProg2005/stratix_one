<script setup>
import { ref, watch } from 'vue';

import AppLayout from '@/Layouts/AppLayout.vue';
import Albom from './Albom.vue';
import PlayBtn from './PlayBtn.vue';
import Panel from './Panel.vue';

const track = ref(null);

function onSelectTrack(selectedTrack) {
    track.value = selectedTrack;
    console.log(track.value);
}
</script>

<template>
    <AppLayout>
        <!--Баннер с карточкой трека-->
        <div class="relative overflow-hidden">
            <div
                class="absolute z-0 inset-0 bg-center bg-cover blur-2xl scale-110"
                style="background-image: url('/img/tt.jpeg')"
            ></div>

            <div class="absolute inset-0 bg-black/40"></div>
            
            <div class="relative z-10 max-w-6xl mx-auto space-y-4 p-4">
                <div class="flex flex-row gap-2">
                    <button><i class="fa-solid fa-arrow-left"></i></button>

                    <div class="flex justify-between items-center">
                        <!--Поиск треков в плейлисте исполнителя-->
                            <div class="flex w-full">
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
                </div>

                <!--Карточка трека-->
                <div v-if="track" class="flex flex-col lg:flex-row gap-4 lg:h-48 space-y-4 lg:space-y-0">
                    <img :src="track.preview" alt=""
                        class="object-contain rounded-2xl h-full"
                    >

                    <div class="flex flex-col h-full justify-between">
                        <div class="flex flex-wrap gap-2">
                            <span class="bg-neon-purple">ПОНРАВИЛОСЬ: 123k <i class="fa-solid fa-heart"></i></span>
                            <span class="bg-neon-green">КОММЕНТАРИЕВ: 11k <i class="fa-solid fa-comment"></i></span>
                        </div>

                        <div class="flex flex-col gap-2 mt-4 lg:mt-0">
                            <span class="extra-title">{{ track.title }}</span>
                            <span class=""><span class="text-md text-gray-400 lg:text-base">Сделано</span> {{ track.artist }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-md lg:text-base text-gray-400">
                            <span>Всего: 50 треков</span>
                            <span class="before:content-['•'] before:mx-2"></span>
                            <span>Продолжительность: 1,40 ч.</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <button class="primary-btn">
                        Добавить в плейлист
                        <i class="fa-solid fa-plus"></i>
                    </button>

                    <button class="title">
                        <i class="fa-solid fa-shuffle"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!--Контент после карточки-->
        <div class="max-w-6xl mx-auto p-4 space-y-4">
            <!--Теги-->
            <div class="flex flex-wrap gap-1 w-98">
                <span class="tag">#Вайб 2022</span>
                <span class="tag">#Тренды 2023</span>
                <span class="tag">#Отдых</span>
                <span class="tag">#52</span>
            </div>

            <Albom @on-track-selected="onSelectTrack" />
        </div>

        <Panel v-if="track" :track="track" />
    </AppLayout>
</template>