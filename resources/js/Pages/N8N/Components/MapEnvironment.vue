<script>
export default {
  bottomPanel: {
    name: 'GEO-тесты',
    height: '400px',
  }
}
</script>

<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useMap } from '@/composables/useMap';

const mapContainer = ref(null);

const { map, initMap } = useMap(mapContainer);

onMounted(async () => {
  await nextTick();
  await initMap();

  if (map.value) {
    map.value.resize();

    const resizeAgain = () => map.value && map.value.resize();

    window.addEventListener('resize', resizeAgain);

    const ro = new ResizeObserver(resizeAgain);
    mapContainer.value && ro.observe(mapContainer.value);

    onBeforeUnmount(() => {
      window.removeEventListener('resize', resizeAgain);
      ro.disconnect();
    });
  }
});
</script> 

<template>
  <div ref="mapSidebar"  
    class="bg-content grid grid-cols-1 md:grid-cols-2 gap-4 h-full"
  >
    <div class="p-4">
      <h2>Окружение</h2>


    </div>

    <div ref="mapContainer" class="relative w-full overflow-hidden" />
  </div>
</template>
