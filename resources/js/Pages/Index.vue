<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import TrackPreview from '@/Components/TrackPreview.vue';
import Search from '@/Components/Search.vue';
import { ref } from 'vue'

const { tracks } = defineProps({
  tracks: Array
});

console.log('TRACKS: ', tracks);
</script>

<template>
  <AppLayout>
    <Search class="p-4" />
    <TrackPreview :tracks="tracks" />
  </AppLayout>
</template>
