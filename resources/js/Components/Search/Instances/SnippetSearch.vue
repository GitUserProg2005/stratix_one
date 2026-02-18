<script setup>
import { ref } from 'vue';
import axios from 'axios';

import Search from '../Search.vue';
import VinylDisc from '@/Pages/Reels/VinylDisc.vue';
import { useFetchSnippets } from '../../../utils/useFetchSnippet';

const foundedSnippets = ref([]);

async function searchSnippets(query) {
    const response = await axios.get(route('snippets.search'), 
    { params: {
        search: query
    } });
    foundedSnippets.value = response.data.snippets;
    return response.data.success ? response.data.snippets : [];
}

function openSnippet(snippet) {
    const otherIds = foundedSnippets.value
        .map(s => s.id)
        .filter(id => id !== snippet.id);

    useFetchSnippets(snippet.id, otherIds);
}
</script>

<template>
    <Search :search-fn="searchSnippets">
        <template #item="{ item }">
            <div
                @click="openSnippet(item)"
                class="flex items-center gap-3 cursor-pointer
                       hover:bg-content p-2 rounded-lg"
            >
                <!-- VINYL -->
                <VinylDisc
                    v-if="item.track?.preview_url"
                    :src="item.track.preview_url"
                    size="w-16 h-16"
                    :is-playing="false"
                />

                <!-- TITLE -->
                <div class="flex flex-col">
                    <span class="text-sm font-medium">
                        {{ item.track?.title ?? 'Без названия' }}
                    </span>

                    <span class="text-xs text-gray-400">
                        Сниппет
                    </span>
                </div>
            </div>
        </template>
    </Search>
</template>