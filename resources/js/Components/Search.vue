<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import Tags from './Tags.vue';
import { fetchTrack } from '@/utils/useFetchTrack';

const search = ref('');
const isSearching = ref(false);
const foundTracks = ref([]);
let debounceTimeout = null;

watch(search, (value) => {
    clearTimeout(debounceTimeout);

    if (!value.trim()) {
        foundTracks.value = [];
        return;
    };

    debounceTimeout = setTimeout(() => {
        searchTracks(value);
    }, 400);
});

async function searchTracks(query) {
    isSearching.value = true;

    try {
        const response = await axios.get(route('tracks.search'), 
        { params: {
            search: query
        } });

        if (response.data.success) {
            foundTracks.value = response.data.tracks;
        }
    } catch (e) {
        console.error('Ошибка при поиске треков: ', e);
    } finally {
        isSearching.value = false;
    }
}
</script>

<template>
    <div class="flex w-full lg:block justify-between items-center relative">
        <div class="flex w-full relative">
            <input
                v-model="search"
                type="text"
                placeholder="Поиск по трекам..."
                class="search-input w-full px-3 py-2 pr-8 border rounded"
            />

            <span class="absolute inset-y-2 right-2 text-gray-400 pointer-events-none">
                <i 
                    class="fa-solid"
                    :class="isSearching ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"
                ></i>
            </span>
        </div>

        <div
            v-if="search"
            class="w-full absolute left-0 top-12 
                bg-body rounded-xl p-4 shadow-xl z-50"
        >
            <h3 v-if="search" class="mb-2 text-sm text-gray-400">Найдено:</h3>

            <div v-if="foundTracks.length || isSearching">
                <div class="space-y-2">
                    <div
                        v-for="track in foundTracks"
                        @click="fetchTrack(track.id)"
                        :key="track.id"
                        class="track flex flex-col lg:flex-row lg:items-center gap-4 
                            cursor-pointer hover:bg-content p-2 rounded-lg"
                    >
                        <div class="flex items-center gap-2">
                            <img :src="track.preview_url" class="track-picture" alt="">

                            <div class="flex flex-col">
                                <span class="font-medium">{{ track.title }}</span>

                                <span class="text-xs context">
                                    Трек • {{ track.artist.name }}
                                </span>
                            </div>
                        </div>

                        <span class="hidden lg:flex">/</span>

                        <Tags :tags="track.tags" />
                    </div>
                </div>
            </div>

            <div v-if="!foundTracks.length && !isSearching" class="text-sm text-gray-400">
                Ничего не найдено :(
            </div>
        </div>
    </div>
</template>