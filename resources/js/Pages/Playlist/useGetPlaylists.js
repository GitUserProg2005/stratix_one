import { ref, onMounted } from 'vue';
import axios from 'axios';

export function useGetPlaylists() {
  const playlists = ref([]);
  const isLoading = ref(false);

  const fetchPlaylists = async () => {
    try {
      isLoading.value = true;
      const response = await axios.get(route('get.playlists'));

      if (response.data.success) {
        playlists.value = response.data.playlists;
      }
    } catch (e) {
      console.error('ERROR TO GET PLAYLISTS: ', e);
    } finally {
      isLoading.value = false;
    }
  };

  // Загружаем при монтировании
  onMounted(fetchPlaylists);

  return {
    playlists,
    isLoading,
    fetchPlaylists, // можно вызвать вручную при необходимости
  };
}
