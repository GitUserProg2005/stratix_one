<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

import AppLayout from '@/Layouts/AppLayout.vue';
import Albom from './Albom.vue';
import PlayBtn from './PlayBtn.vue';
import Panel from './Panel.vue';
import Search from '@/Components/Search.vue';
import Tags from '@/Components/Tags.vue';

const props = defineProps({
    track: Array,
    artist_tracks: Object,
    rightNow: Number
});

const track = ref(props.track.data);
const playRightNow = ref(!!props.rightNow);

function selectTrack(selectedTrack) {
  track.value = selectedTrack;
  playRightNow.value = true;
}
</script>

<template>
    <AppLayout>
        <!--Баннер с карточкой трека-->
        <div class="relative overflow-hidden">
            <div
                class="absolute z-0 inset-0 bg-center bg-cover blur-2xl scale-110"
                :style="{ backgroundImage: `url(${track.preview_url})` }"
            ></div>

            <div class="absolute inset-0 bg-black/40"></div>
            
            <div class="relative z-10 max-w-6xl mx-auto space-y-4 p-4">
                <div class="flex flex-row gap-2">
                    <button @click="() => router.get(route('tracks.index'))"><i class="fa-solid fa-arrow-left"></i></button>

                    <Search />
                </div>

                <!--Карточка трека-->
                <div v-if="track" class="flex flex-col lg:flex-row gap-4 lg:h-48 space-y-4 lg:space-y-0">
                    <img :src="track.preview_url" alt=""
                        class="object-contain rounded-2xl h-full"
                    >

                    <div class="flex flex-col h-full justify-between">
                        <div class="flex flex-wrap gap-2">
                            <span class="bg-neon-purple">ПОНРАВИЛОСЬ: 123k <i class="fa-solid fa-heart"></i></span>
                            <span class="bg-neon-green">КОММЕНТАРИЕВ: 11k <i class="fa-solid fa-comment"></i></span>
                        </div>

                        <div class="flex flex-col gap-2 mt-4 lg:mt-0">
                            <span class="extra-title">{{ track.title }}</span>
                            <span class=""><span class="text-md text-gray-400 lg:text-base">Сделано</span> {{ track.artist.name }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-md lg:text-base text-gray-400">
                            <span>Всего: {{ track.artist.tracks_count }} треков</span>
                            <span class="before:content-['•'] before:mx-2"></span>
                            <span>Продолжительность: 1,40 ч.</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <button class="primary-btn flex items-center gap-4">
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
            <Tags :tags="track.tags" />
            <Albom :tracks="artist_tracks.data" 
                @trackSelected="selectTrack" 
                :artist_name="track.artist.name"
            />
        </div>

        <Panel v-if="track" :track="track" :playRightNow="playRightNow" />
    </AppLayout>
</template>