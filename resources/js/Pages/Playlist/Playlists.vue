<script setup>
import { ref, onMounted } from 'vue';

import { useCreatePlaylist } from './useCreatePlaylist';
import { useGetPlaylist } from './useGetPlaylist';

const playlists = ref([]);
const isLoading = ref(false);

async function getPlaylists() {
    isLoading.value = true;

    try {
        const response = await axios.get(route('get.playlists'));

        if (response.data.success) {
            playlists.value = response.data.playlists;
        }
    } catch (e) {
        console.error('ERROR TO GET PLAYLISTS: ', e);
    } finally {
        isLoading.value = false;
    }
}

async function handleCreatePlaylist() {
    const newPlaylist = await useCreatePlaylist(playlists.value);

    if (newPlaylist) {
        playlists.value.push({
            ...newPlaylist,
            prewiew: '/img/playlist_default.png',
            duration: '0:00',
            tracks_count: 0
        });
    }
}


onMounted(() => {
    getPlaylists();
});  
</script>

<template>
    <section class="flex flex-col 
          space-y-4 px-4">
    <div class="flex flex-row items-center justify-between">
      <h2 class="title"><i class="fa-regular fa-folder-open"></i> Плейлисты</h2>

      <button @click="handleCreatePlaylist" class="icon-btn">
        <i class="fa-solid fa-plus"></i>                    
      </button>
    </div>

    <section class="space-y-4">
        <div v-if="playlists.length">
            <div 
                v-for="(playlist, i) in playlists"
                :key="i"
                @click="useGetPlaylist(playlist.id)"
                class="track rounded-l-2xl rounded-r-full flex items-center px-6 py-1 cursor-pointer"
            >
                <div class="flex flex-wrap h-full gap-2">
                    <img v-if="playlist.preview" :src="playlist.preview" class="track-picture" alt="">
                    <img v-else src="/img/playlist_default.png" class="track-picture" alt="">
                    
                    <div class="flex flex-col justify-between">
                        <span class="">{{ playlist.title }}</span>
                        <span class="context"><i class="fa-solid fa-headphones"></i> {{ playlist.tracks_count }} треков</span>
                        <span class="context"><i class="fa-regular fa-clock"></i> {{ playlist.duration }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else>
            <div class="bg-content p-4 rounded-2xl flex items-center justify-center">
                Тут пока что ничего нет :(
            </div>
        </div>
    </section>
    </section>
</template>