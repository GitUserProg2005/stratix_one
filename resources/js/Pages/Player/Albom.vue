<script setup>
import { ref, watch } from 'vue';
import PlayBtn from './PlayBtn.vue';
import { fetchTrack } from '@/utils/useFetchTrack';

const props = defineProps({
    tracks: Array,
    artist_name: String
}); 

const emit = defineEmits(['trackSelected']);

function onClickTrack(track) {
  emit('trackSelected', track); 
}
</script>

<template>
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
                        v-for="(track, i) in tracks"
                        :key="i"
                        @click="onClickTrack(track)"
                        class="track group"
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

                        <td class="px-4 py-3 flex-wrap items-center gap-4">
                            <img :src="track.preview_url" class="track-picture" alt="">
                            <div class="flex flex-col justify-between h-full">
                                <span class="text-white font-medium">
                                    {{ track.title }}
                                </span>
                            </div>
                        </td>

                        <td class="px-4 py-3 text-gray-300">
                            {{ track.artist.name }}
                        </td>

                        <td class="px-4 py-3 text-gray-300">
                            11k
                        </td>

                        <td class="px-4 py-3 text-gray-300">
                            120k
                        </td>

                        <td class="px-4 py-3 text-gray-300">
                            {{ track.release.created_at }}
                        </td>

                        <td class="px-4 py-3 text-right text-gray-400">
                            {{ track.duration }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>