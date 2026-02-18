import { ref } from 'vue';
import axios from 'axios';


export async function useCreatePlaylist(playlists=null) {
    const createdPlaylist = ref({});

    const title = `Плейлист #${playlists.length+1}`;
    const fd = new FormData();

    fd.append('title', title);

    try {
        const response = await axios.post(route('create.playlists'), fd);

        if (response.data.success) {
            createdPlaylist.value = response.data.playlist;
        }
    } catch (e) {
        console.error('ERROR TO CREATE PLAYLIST: ', e);
    } finally {
        return createdPlaylist.value;
    }
}