<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import EditableTitle from '@/Components/EditableTitle.vue';
import PlayBtn from '../Player/PlayBtn.vue';
import Back from '../Player/Back.vue';
import { fetchTrack } from '@/utils/useFetchTrack';

const GRADIENTS = [
    'linear-gradient(to bottom, #1db954 0%, #1a472a 40%, #0f0f0f 100%)', // зелёный
    'linear-gradient(to bottom, #e67e22 0%, #c0392b 40%, #0f0f0f 100%)',  // оранжевый
    'linear-gradient(to bottom, #ecf0f1 0%, #7f8c8d 40%, #0f0f0f 100%)', // белый/серый
    'linear-gradient(to bottom, #e74c3c 0%, #922b21 40%, #0f0f0f 100%)',  // красный
];

const props = defineProps({
    playlist: Object
});

const playlist = ref(JSON.parse(JSON.stringify(props.playlist)));
const headerGradient = ref(GRADIENTS[0]);

onMounted(() => {
    headerGradient.value = GRADIENTS[Math.floor(Math.random() * GRADIENTS.length)];
});

function onTitleUpdated(newTitle) {
    playlist.value.data.title = newTitle;
}

function formatDuration(totalSeconds) {
    if (!totalSeconds) return '0:00';
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = Math.round(totalSeconds % 60);
    if (hours > 0) {
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

async function removeTrack(trackId, e) {
    e.stopPropagation();
    try {
        const url = route('playlist.remove.track', { playlistId: playlist.value.data.id, trackId });
        const response = await axios.delete(url);
        if (response.data.success) {
            const idx = playlist.value.data.tracks.findIndex(t => t.id === trackId);
            if (idx !== -1) {
                playlist.value.data.tracks.splice(idx, 1);
                playlist.value.data.tracks_count = playlist.value.data.tracks.length;
                const totalSeconds = playlist.value.data.tracks.reduce((sum, t) => sum + (Number(t.duration) || 0), 0);
                playlist.value.data.duration = formatDuration(totalSeconds);
            }
        }
    } catch (err) {
        console.error('Remove track error:', err);
    }
}

async function deletePlaylist() {
    if (!confirm('Удалить плейлист? Это действие нельзя отменить.')) return;
    try {
        const url = route('playlist.destroy', playlist.value.data.id);
        const response = await axios.delete(url);
        if (response.data.success) {
            router.visit(route('tracks.index'));
        }
    } catch (err) {
        console.error('Delete playlist error:', err);
    }
}

function onBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('tracks.index'));
    }
}
</script>

<template>
    <AppLayout>
        <div class="w-full">
            <!-- Верхняя часть: случайный градиент (зелёный / оранжевый / белый / красный) → чёрный -->
            <div
                class="relative pt-6 pb-8 px-4 mb-0"
                :style="{ background: headerGradient }"
            >
                <div class="flex flex-row justify-between items-center">
                    <Back :back-url="route('tracks.index')" @back="onBack" />
                    <button
                        type="button"
                        class="icon-btn flex text-black items-center justify-center hover:text-white transition"
                        title="Удалить плейлист"
                        @click="deletePlaylist"
                    >
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>

                <div class="flex flex-wrap gap-4 h-full mt-4">
                    <img :src="playlist.data.preview_url || '/img/playlist_default.png'" class="track-picture-card-lg shadow-2xl" alt="">
                    <div class="flex flex-col justify-between gap-2">
                        <div>
                            <h3 class="text-white/90 text-sm font-medium uppercase tracking-wider">Мой плейлист</h3>
                            <EditableTitle
                                :model-value="playlist.data.title"
                                :playlist-id="playlist.data.id"
                                :max-length="20"
                                @update:model-value="onTitleUpdated"
                            />
                        </div>

                        <div class="flex flex-col gap-2 context text-white/80">
                            <span>{{ playlist.data.artists_names }}</span>
                            <span><i class="fa-solid fa-music mr-2"></i> {{ playlist.data.tracks_count }} треков</span>
                            <span><i class="fa-regular fa-clock mr-2"></i> Длительность: {{ playlist.data.duration }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Таблица треков -->
            <div class="w-full overflow-x-auto p-4 pt-2">
                <div class="overflow-y-auto pr-2 custom-scroll">
                    <table class="w-full border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-left context">
                                <th class="px-4 font-medium">#</th>
                                <th class="px-4 font-medium">Название</th>
                                <th class="px-4 font-medium">Исполнитель</th>
                                <th class="px-4 font-medium text-right"><i class="fa-regular fa-clock"></i></th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(track, i) in playlist.data.tracks"
                                :key="track.id"
                                @click="fetchTrack(track.id, { back: route('playlist.show', playlist.data.id) })"
                                class="track group cursor-pointer hover:bg-gray-800 transition-all"
                            >
                                <td class="px-4 py-3 w-12 relative">
                                    <!-- номер -->
                                    <span
                                        class="absolute inset-0 flex items-center justify-center
                                               text-white font-medium
                                               opacity-100 group-hover:opacity-0 transition"
                                    >
                                        {{ i + 1 }}
                                    </span>

                                    <!-- play -->
                                    <div
                                        class="absolute inset-0 flex items-center justify-center
                                               opacity-0 group-hover:opacity-100 transition"
                                    >
                                        <PlayBtn @click.stop="fetchTrack(track.id, { back: route('playlist.show', playlist.data.id) })" :width="36" />
                                    </div>
                                </td>

                                <td class="px-4 py-3 flex flex-wrap items-center gap-4">
                                    <img :src="track.preview_url || '/img/track_default.png'" class="track-picture" alt="">
                                    <div class="flex flex-col justify-between h-full">
                                        <span class="text-white font-medium">{{ track.title }}</span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-gray-300">
                                    {{ track.release?.artist_name || 'Неизвестно' }}
                                </td>

                                <td class="px-4 py-3 text-right text-gray-400">
                                    {{ track.duration || '0:00' }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        class="icon-btn flex items-center justify-center opacity-0 group-hover:opacity-100 transition hover:bg-red-500 hover:border-red-500 hover:text-white"
                                        title="Удалить из плейлиста"
                                        @click="removeTrack(track.id, $event)"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
