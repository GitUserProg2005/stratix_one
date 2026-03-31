<script setup>
import MapLayout from '@/Layouts/MapLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { nextTick, onMounted, onBeforeUnmount, ref, computed } from 'vue';
import { useMap } from '@/composables/useMap.js';
import PassengerPanel from './Components/PassengerPanel.vue';
import DriverPanel from './Components/DriverPanel.vue';
import NearestCars from './Components/NearestCars.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const isPassenger = computed(() => !user.value || user.value.role === 'passenger');

const mapContainer = ref(null);
const nearestCarsRef = ref(null);
const nearestCarsCount = ref(0);
const pointACenter = ref(null);
const driverLiveLat = ref(null);
const driverLiveLng = ref(null);

function setDriverLivePosition(lat, lng) {
  driverLiveLat.value = lat != null ? Number(lat) : null;
  driverLiveLng.value = lng != null ? Number(lng) : null;
}

const { map, initMap, addResultMarkers, clearResultMarkers, setMarkerA, setMarkerB, flyTo, fitBoundsToMarkers } = useMap(mapContainer);

onMounted(async () => {
  await nextTick();
  await initMap();
  await nextTick();
  if (map.value) {
    map.value.resize();
    const resizeAgain = () => map.value && map.value.resize();
    requestAnimationFrame(resizeAgain);
    setTimeout(resizeAgain, 100);
    window.addEventListener('resize', resizeAgain);
    const ro = mapContainer.value ? new ResizeObserver(resizeAgain) : null;
    if (mapContainer.value) ro.observe(mapContainer.value);
    onBeforeUnmount(() => {
      window.removeEventListener('resize', resizeAgain);
      ro?.disconnect();
    });
  }
});
</script>

<template>
  <Head title="Карта" />
  <MapLayout>
    <div ref="mapContainer" class="relative w-full min-h-[40vh] h-full min-h-0 overflow-hidden lg:col-start-2 lg:row-start-2 bg-content-outline" />

    <div class="absolute left-0 right-0 z-10 px-3 pointer-events-none" style="top: calc(var(--app-header-height, 0px) + 0.75rem);">
      <div class="pointer-events-auto max-w-full">
        <NearestCars
          ref="nearestCarsRef"
          :map="map"
          :point-a-center="pointACenter"
          :user="user"
          @update:count="nearestCarsCount = $event"
          @driver-location-updated="(e) => setDriverLivePosition(e?.lat, e?.lng)"
        />
      </div>
    </div>

    <PassengerPanel
      v-if="isPassenger"
      :map="map"
      :set-marker-a="setMarkerA"
      :set-marker-b="setMarkerB"
      :fly-to="flyTo"
      :clear-result-markers="clearResultMarkers"
      :add-result-markers="addResultMarkers"
      :fit-bounds-to-markers="fitBoundsToMarkers"
      :nearest-cars-count="nearestCarsCount"
      :open-nearest-cars="() => nearestCarsRef?.value?.toggleNearestCarsModal?.()"
      @update:point-a-center="pointACenter = $event"
    />

    <DriverPanel
      v-else
      :map="map"
      :user="user"
      :driver-live-lat="driverLiveLat"
      :driver-live-lng="driverLiveLng"
      :set-marker-a="setMarkerA"
      :set-marker-b="setMarkerB"
      :fly-to="flyTo"
    />
  </MapLayout>
</template>
