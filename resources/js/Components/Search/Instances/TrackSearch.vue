<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { fetchTrack } from '@/utils/useFetchTrack';

import Search from '../Search.vue';
import Tags from '@/Components/Tags.vue';

async function searchTracks(query) {
    const response = await axios.get(route('tracks.search'), 
    { params: {
        search: query
    } });

    return response.data.success 
        ? response.data.tracks 
        : [];
}
</script>

<template>
    <Search :searchFn="searchTracks">
        <template #item="{ item }">
            <div
                @click="fetchTrack(item.id)"
                class="track flex flex-col lg:flex-row lg:items-center gap-4
                    cursor-pointer hover:bg-content p-2 rounded-lg"
            >
                <div class="flex items-center gap-2">
                    <img
                        :src="item.preview_url"
                        class="track-picture"
                    />

                    <div class="flex flex-col">
                        <span class="font-medium">
                            {{ item.title }}
                        </span>

                        <span class="text-xs">
                            Трек • {{ item.artist.name }}
                        </span>
                    </div>
                </div>

                <span class="hidden lg:flex">/</span>

                <Tags :tags="item.tags" />
            </div>
        </template>
    </Search>
</template>