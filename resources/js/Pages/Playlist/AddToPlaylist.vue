<script setup>
import { ref } from 'vue';
import axios from 'axios';
import ToastrMessage from '@/Components/ToastrMessage.vue';

const props = defineProps({
  trackId: {
    type: Number,
    required: true
  }
});

const playlists = ref([]);
const isLoading = ref(false);

const isOpened = ref(false);
const selectedPlaylistId = ref(null);
const toast = ref({ isShow: false, title: '' });

// Выбираем плейлист
function selectPlaylist(id) {
  selectedPlaylistId.value = id;
  addTrackToPlaylist(selectedPlaylistId.value, props.trackId);
}

// Загружаем только плейлисты, в которых нет текущего трека
async function fetchPlaylistsWithoutTrack(trackId) {
  try {
    isLoading.value = true;
    const response = await axios.get(route('get.playlists.without.track'), {
      params: { track_id: trackId }
    });

    if (response.data.success) {
      playlists.value = response.data.playlists;
    }
  } catch (e) {
    console.error('ERROR TO GET PLAYLISTS WITHOUT TRACK:', e);
  } finally {
    isLoading.value = false;
  }
}

// Добавление трека в плейлист
async function addTrackToPlaylist(playlistId, trackId) {
  try {
    const response = await axios.post(route('playlist.add.track', playlistId), {
      trackId: trackId
    });

    if (response.data.success) {
      toast.value.title = 'Трек добавлен в плейлист!';
      toast.value.isShow = true;
      // Можно обновить список плейлистов, чтобы убрать выбранный
      playlists.value = playlists.value.filter(p => p.id !== playlistId);

    }
  } catch (e) {
    toast.value.title = 'Ошибка при добавлении трека';
    toast.value.isShow = true;
    console.error('ERROR TO ADD TRACK: ', e);
  } finally {
    isOpened.value = false;
    selectedPlaylistId.value = null;
  }
}

// Открытие меню — подгружаем плейлисты
function toggleMenu() {
  isOpened.value = !isOpened.value;
  if (isOpened.value) {
    fetchPlaylistsWithoutTrack(props.trackId);
  }
}
</script>

<template>
  <div class="relative">
    <button @click="toggleMenu" class="primary-btn flex items-center gap-4">
      Добавить в плейлист
      <i class="fa-solid fa-plus"></i>
    </button>

    <div
      class="absolute right-0 bottom-[60px] w-48 z-50 bg-body rounded-2xl p-4"
      v-if="isOpened"
    >
      <div class="flex items-center justify-between mb-1">
        <span class="context">Выберите плейлист</span>
        <button
          type="button"
          @click="isOpened = false"
          class="text-gray-400 hover:text-white transition p-1 -m-1"
          aria-label="Закрыть"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="flex flex-col space-y-2 mt-2 overflow-y-auto h-auto">
        <div v-if="isLoading">Загрузка...</div>
        <span
          v-for="playlist in playlists"
          :key="playlist.id"
          @click="selectPlaylist(playlist.id)"
          class="track p-2 cursor-pointer hover:bg-gray-200 rounded"
        >
          {{ playlist.title }}
        </span>
      </div>
    </div>

    <ToastrMessage :isShow="toast" :title="toast.title" />
  </div>
</template>
