import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useGetPlaylist(playlistId) {
    router.get(route('playlist.show', playlistId));
}