<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { useUpdateCarPosition } from '@/composables/useUpdateCarPosition.js';
import { useGeoCells } from '@/composables/useGeoCells.js';

const props = defineProps({
  map: { type: Object, default: null },
  user: { type: Object, default: null },
  pointACenter: { type: Object, default: null },
});
const emit = defineEmits(['update:count', 'driver-location-updated']);
const mapRef = computed(() => props.map?.value ?? props.map);
const user = computed(() => props.user);
const nearbyDrivers = ref([]);
const isNearestCarsOpen = ref(false);
const currentMainCell = ref(null);

function mergeDriverIntoList(driver) {
  const list = [...nearbyDrivers.value];
  const idx = list.findIndex((d) => d.id === driver.id);
  const merged = { ...(list[idx] || {}), ...driver };
  if (idx >= 0) list[idx] = merged;
  else list.push(merged);
  nearbyDrivers.value = list;
}

const { startTestDrivers, updatePosition, drawDriverMarker, clearDriverMarkers, driverMovementAnimation } = useUpdateCarPosition({
  mapRef,
  user_id: user.value?.id ?? null,
  onDriverStale: (driverId) => { nearbyDrivers.value = nearbyDrivers.value.filter((d) => d.id !== driverId); },
});
const { getCells, updateCurrentCells } = useGeoCells({
  drawDriverMarker,
  driverMovementAnimation,
  mapRef,
  currentUserId: user.value?.id ?? null,
  onDriverCoordsUpdated: mergeDriverIntoList,
});

let stopTestDrivers = null;
watch(() => user.value?.role, (role) => {
  if (role === 'driver') {
    stopTestDrivers = startTestDrivers((driver) => {
      emit('driver-location-updated', { lat: driver.lat, lng: driver.lng });
      updateCurrentCells(driver.lat, driver.lng);
      updatePosition(driver.lat, driver.lng);
    });
  } else {
    stopTestDrivers?.();
    stopTestDrivers = null;
    emit('driver-location-updated', { lat: null, lng: null });
  }
}, { immediate: true });

watch(() => {
  const map = mapRef.value;
  const center = map?.getCenter?.();
  const role = user.value?.role ?? null;
  const pointA = props.pointACenter ?? null;
  return [map, role, pointA, center?.lat, center?.lng];
}, ([mapInstance, role, pointA, centerLat, centerLng]) => {
  if (!mapInstance) return;
  let lat;
  let lng;
  if (role === 'passenger' && pointA?.lat != null && pointA?.lng != null) {
    lat = pointA.lat;
    lng = pointA.lng;
  } else {
    lat = centerLat;
    lng = centerLng;
  }
  if (lat == null || lng == null) return;
  const cells = getCells(lat, lng);
  const mainCell = cells[0];
  if (mainCell !== currentMainCell.value) {
    currentMainCell.value = mainCell;
    clearDriverMarkers();
    nearbyDrivers.value = [];
  }
  updateCurrentCells(lat, lng);
}, { immediate: true });

watch(nearbyDrivers, (list) => emit('update:count', Array.isArray(list) ? list.length : 0), { immediate: true });
function toggleNearestCarsModal() { isNearestCarsOpen.value = !isNearestCarsOpen.value; }
defineExpose({ toggleNearestCarsModal });
onBeforeUnmount(() => { stopTestDrivers?.(); clearDriverMarkers(); });
</script>

<template>
  <template v-if="nearbyDrivers.length > 0">
    <button type="button" class="pointer-events-auto flex items-center gap-2 bg-content/95 rounded-2xl shadow-lg border border-black/10 dark:border-white/10 px-4 py-2.5 text-left backdrop-blur-sm" @click="toggleNearestCarsModal">
      <i class="fa-solid fa-car context"></i>
      <span class="t-small font-medium">Поблизости {{ nearbyDrivers.length }} водителей</span>
    </button>
    <Teleport to="body">
      <div class="fixed inset-0 z-[9999] flex flex-col bg-body transition-transform duration-300 ease-out" :class="isNearestCarsOpen ? 'translate-y-0' : 'translate-y-full'">
        <div class="flex items-center justify-between shrink-0 px-4 py-3 border-b border-black/10 dark:border-white/10">
          <p class="t-small font-medium">Поблизости {{ nearbyDrivers.length }} водителей</p>
          <button type="button" class="w-10 h-10 rounded-full flex items-center justify-center context hover:bg-content" @click="toggleNearestCarsModal"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto">
          <div v-for="driver in nearbyDrivers" :key="driver.id" class="flex items-center gap-2.5 px-4 py-2.5 border-b border-black/5 dark:border-white/10">
            <div class="min-w-0 flex-1">
              <p class="t-small font-semibold truncate">{{ driver.name ?? 'Водитель' }}</p>
              <p class="context truncate">{{ driver.phone ?? '' }}</p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </template>
</template>
