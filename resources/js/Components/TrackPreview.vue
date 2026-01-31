<script setup>
import { ref } from 'vue';

import { fetchTrack } from '@/utils/useFetchTrack';

const props = defineProps({
    tracks: Array,
});

const theBestTracks = ref([
  { preview: '/img/tt.jpeg', title: 'Knock Knock', artist: 'Wolfgang Murgen', duration: '3:42' },
  { preview: '/img/skelet.png', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },
  { preview: '/img/pic.jpeg', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },
  { preview: '/img/test.jpg', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },
  { preview: '/img/test.jpg', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },

])

const recommended = ref([
  { preview: '/img/tt.jpeg', title: 'Knock Knock', artist: 'Wolfgang Murgen', duration: '3:42' },
  { preview: '/img/skelet.png', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },
  { preview: '/img/pic.jpeg', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },
  { preview: '/img/test.jpg', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },
  { preview: '/img/test.jpg', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },
])


console.log(props.tracks.data.map(t => ({ title: t.title, preview: t.preview })));

</script>

<template>
    <div class="p-4 space-y-12">
        

        <div class="space-y-4">
            <div>
                <h2 class="extra-title">Лучшее 2025</h2>
                <span class="context">Хиты 2025 года</span>
            </div>

            <section class="grid grid-cols-2
            md:grid-cols-3 lg:grid-cols-5
            gap-2 lg:gap-4"
            >
                <div v-for="(track, i) in theBestTracks" :key="index"
                    class=""
                >
                    <img :src="track.preview" class="track-picture-card-lg" alt="">
                    <h3 class="title mt-1">{{ track.title }}</h3>
                    <h3 class="context">{{ track.artist }}</h3>
                </div>
            </section>
        </div>

        <div class="space-y-4">
            <div>
                <h2 class="extra-title">Вы недавно слушали</h2>
                <span class="text-gray-400 mt-3">На основе ваших интересов</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div
                    v-for="track in tracks.data" :key="track.id" class="content-p-0 rounded-2xl flex items-center gap-4 cursor-pointer"
                    @click="fetchTrack(track.id)"
                >
                    <img :src="track.preview_url" class="track-picture-card-sm" alt="">
                    <div class="flex flex-col">
                        <span class="title">{{ track.title }}</span>
                        <span class="context">{{ track.artist.name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                
                <h2 class="extra-title">Рекомендации на сегодня</h2>
                <span class="context">На основе ваших недавних прослушиваний</span>
            </div>

            <section class="grid grid-cols-2
            md:grid-cols-3 lg:grid-cols-5
            gap-2 lg:gap-4"
            >
                <div v-for="(track, i) in recommended" :key="index"
                    class=""
                >
                    <img :src="track.preview" class="track-picture-card-lg" alt="">
                    <h3 class="title mt-1">{{ track.title }}</h3>
                    <h3 class="context">{{ track.artist }}</h3>
                </div>
            </section>
        </div>
    </div>
</template>