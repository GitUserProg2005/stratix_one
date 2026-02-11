<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

import AppLayout from '@/Layouts/AppLayout.vue';
import Albom from './Albom.vue';
import Back from './Back.vue';
import LikeButton from './LikeButton.vue';
import Panel from './Panel.vue';
import TrackSearch from '@/Components/Search/Instances/TrackSearch.vue';
import Tags from '@/Components/Tags.vue';
import AddToPlaylist from '../Playlist/AddToPlaylist.vue';
import { stopListen } from '@/utils/stopListen.js';

const props = defineProps({
    track: Array,
    artist_tracks: Object,
    rightNow: Number,
    /** URL для кнопки «Назад» (с главной или со страницы плейлиста) */
    backUrl: {
        type: String,
        default: null
    }
});

const track = ref(props.track.data);
const playRightNow = ref(!!props.rightNow);
const isLiked = ref(false);
const panelRef = ref(null);

function onBack() {
    const state = panelRef.value?.getListenState?.();
    if (state) {
        stopListen('back', state.listenTime, state.duration, track.value.id);
    }
    if (props.backUrl) {
        router.visit(props.backUrl);
    } else {
        router.get(route('tracks.index'));
    }
}

function selectTrack(selectedTrack) {
  track.value = selectedTrack;
  playRightNow.value = true;
}

function toggleLike() {
  isLiked.value = !isLiked.value;
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
            
            <div class="relative z-10 max-w-6xl mx-auto space-y-8 p-4">
                <div class="flex flex-row gap-2">
                    <Back :back-url="backUrl" @back="onBack" />
                    <TrackSearch />
                </div>

                <!--Карточка трека-->
                    <div v-if="track" class="flex flex-col lg:flex-row gap-4 lg:h-48 space-y-4 lg:space-y-0">
                        <div class="relative">
                            <img :src="track.preview_url" alt=""
                                class="object-contain rounded-2xl h-full"
                            >

                            <span class="absolute bottom-2 left-2 bg-white text-black px-4 py-1 rounded-xl long-title">
                                2026
                            </span>

                            <div class="lg:hidden">
                                <LikeButton :absolute="true" :is-liked="isLiked" @toggle="toggleLike" />
                            </div>
                        </div>
                        <div class="flex flex-col h-full justify-between">
                            <div class="flex flex-col gap-2">
                                <span class="extra-title black">{{ track.title }}</span>
                                <span class=""><span class="text-md text-gray-400 lg:text-base">Сделано</span> {{ track.artist.name }}</span>
                            </div>

                            <div class="flex flex-col lg:flex-row lg:items-center lg:gap-2 mt-4 lg:mt-0 text-md lg:text-base text-gray-400">
                                <span><i class="fa-solid fa-music mr-2"></i> Всего: {{ track.artist.tracks_count }} треков</span>
                                <span class="hidden lg:flex before:content-['•'] before:mx-2"></span>
                                <span>Продолжительность: {{ track.release.total_duration }}</span>
                            </div>
                        </div>
                    </div>

                <div class="flex flex-wrap items-center gap-4">
                    <AddToPlaylist :trackId="track.id" />

                    <div class="hidden lg:block">
                        <LikeButton :absolute="false" :is-liked="isLiked" @toggle="toggleLike" />
                    </div>

                    <button class="title">
                        <i class="fa-solid fa-shuffle"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!--Контент после баннера-->
        <div class="max-w-6xl mx-auto p-4 space-y-8 z-40">
            <Tags :tags="track.tags" />

            <div class="grid grid-cols-1 lg:grid-cols-1 gap-4">
                <div class="space-y-4">
                    <h3 class="title">Другие треки исполнителя</h3>

                    <Albom :tracks="artist_tracks.data" 
                        @trackSelected="selectTrack" 
                        :artist_name="track.artist.name"
                    />
                </div>
            </div>
        </div>

        <!--Плеер-->
        <Panel ref="panelRef" v-if="track" :track="track" :playRightNow="playRightNow" />
    </AppLayout>
</template>