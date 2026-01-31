<script setup>
import { ref } from 'vue';

const playlists = ref([
  { preview: '/img/tt.jpeg', title: 'В дорогу', duration: '3:42' },
  { preview: '/img/skelet.png', title: 'Вайб 2023', duration: '4:10' },
  { preview: '/img/pic.jpeg', title: 'Тренировка', duration: '4:10' },
  { preview: '/img/test.jpg', title: 'Любимое', duration: '4:10' },
])
</script>

<template>
    <section class="flex flex-col 
          space-y-4 px-4">
    <div class="flex flex-row items-center justify-between">
      <h2 class="title"><i class="fa-regular fa-folder-open"></i> Плейлисты</h2>

      <button class="icon-btn">
        <i class="fa-solid fa-plus"></i>                    
      </button>
    </div>

    <section class="space-y-4">
        <div 
            v-for="(playlist, i) in playlists"
            :key="i"
            class="track rounded-l-2xl rounded-r-full flex items-center px-6 py-1"
        >
            <div class="flex flex-wrap h-full gap-2">
                <img :src="playlist.preview" class="track-picture" alt="">

                <div class="flex flex-col justify-between">
                    <span class="">{{ playlist.title }}</span>
                    <span class="context"><i class="fa-solid fa-headphones"></i> 230 треков</span>
                    <span class="context"><i class="fa-regular fa-clock"></i> 1,24 ч.</span>
                </div>
            </div>
        </div>
    </section>
    </section>
</template>