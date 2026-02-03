<script setup>
import { ref } from 'vue';
import PlayBtn from './PlayBtn.vue';

const props = defineProps({
    playlist: Object // ожидаем PlaylistResource
});

const emit = defineEmits(['trackSelected']);

function onClickTrack(track) {
    emit('trackSelected', track);
}
</script>

<template>
    <div class="w-full p-4">
        <!-- Верхняя часть с превью и информацией о плейлисте -->
        <div class="flex flex-wrap gap-4 h-full mb-6">
            <img :src="playlist.data.preview_url || '/img/playlist_default.png'" class="track-picture-card-lg" alt="">
            <div class="flex flex-col justify-between gap-2">
                <div>
                    <h3 class="">Мой плейлист</h3>
                    <h2 class="extra-title">{{ playlist.data.title }}</h2>
                </div>

                <div class="flex flex-col gap-2 context">
                    <span><i class="fa-solid fa-music mr-2"></i> {{ playlist.data.tracks_count }} треков</span>
                    <span><i class="fa-regular fa-clock mr-2"></i> Длительность: {{ playlist.data.duration }}</span>
                </div>
            </div>
        </div>

        <!-- Таблица треков -->
        <div class="w-full overflow-x-auto">
            <div class="max-h-[360px] overflow-y-auto pr-2 custom-scroll">
                <table class="w-full border-separate border-spacing-y-2">
                    <thead>
                        <tr class="text-left context">
                            <th class="px-4 font-medium">#</th>
                            <th class="px-4 font-medium">Название</th>
                            <th class="px-4 font-medium">Исполнитель</th>
                            <th class="px-4 font-medium">Понравилось</th>
                            <th class="px-4 font-medium">Прослушало</th>
                            <th class="px-4 font-medium">Создано</th>
                            <th class="px-4 font-medium text-right"><i class="fa-regular fa-clock"></i></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(track, i) in playlist.data.tracks"
                            :key="track.id"
                            @click="onClickTrack(track)"
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
                                    <PlayBtn @click.stop="onClickTrack(track)" :width="36" />
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

                            <td class="px-4 py-3 text-gray-300">0</td>
                            <td class="px-4 py-3 text-gray-300">0</td>

                            <td class="px-4 py-3 text-gray-300">
                                {{ track.release?.created_at || '-' }}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-400">
                                {{ track.duration || '0:00' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
