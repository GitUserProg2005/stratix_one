<script setup>
import { ref, watch } from 'vue';
import PlayBtn from './PlayBtn.vue';

const tracks = ref([
  { preview: '/img/tt.jpeg', title: 'Knock Knock', artist: 'Wolfgang Murgen', duration: '3:42' },
  { preview: '/img/skelet.png', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },
  { preview: '/img/pic.jpeg', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },
  { preview: '/img/test.jpg', title: 'Night Drive', artist: 'Neon Waves', duration: '4:10' },
])

const emit = defineEmits(['on-track-selected']);
const selectedTrack = ref(null);

function selectTrack(trackId) {
    selectedTrack.value = tracks.value[trackId];
    emit('on-track-selected', selectedTrack.value);
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
                        <th class="px-4 font-medium">Создано</th>
                        <th class="px-4 font-medium text-right"><i class="fa-regular fa-clock"></i></th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="(track, i) in tracks"
                        :key="i"
                        @click="selectTrack(i)"
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
                                <PlayBtn :width="36" />
                            </div>
                        </td>

                        <td class="px-4 py-3 flex-wrap items-center gap-4">
                            <img :src="track.preview" class="track-picture" alt="">
                            <div class="flex flex-col justify-between h-full">
                                <span class="text-white font-medium">
                                    {{ track.title }}
                                </span>
                            </div>
                        </td>

                        <td class="px-4 py-3 text-gray-300">
                            {{ track.artist }}
                        </td>


                        <td class="px-4 py-3 text-gray-300">
                            5 часов назад
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