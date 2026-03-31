import { computed, ref, watch } from 'vue';
import { clearRoute, drawRoute } from '@/composables/useRouteLayer.js';

export function useRoutePreview({ mapRef, mapActions = {} }) {
  const pointA = ref(null);
  const pointB = ref(null);
  const duration = ref(0);
  const distance = ref(0);
  const hasRouteInfo = computed(() => duration.value > 0 || distance.value > 0);

  function displayPlace(place, fallback) {
    return place?.display_name ?? fallback;
  }

  function formatDuration(seconds) {
    if (!seconds || seconds <= 0) return null;
    const totalMinutes = Math.round(seconds / 60);
    if (totalMinutes < 60) return `${totalMinutes} мин`;
    const h = Math.floor(totalMinutes / 60);
    const m = totalMinutes % 60;
    return m ? `${h} ч ${m} мин` : `${h} ч`;
  }

  function formatDistance(meters) {
    if (!meters || meters <= 0) return null;
    const km = meters / 1000;
    return km >= 1 ? `${km.toFixed(1).replace('.', ',')} км` : `${Math.round(meters)} м`;
  }

  function setPointA(place) {
    pointA.value = place;
    mapActions?.clearResultMarkers?.();
    if (place) {
      mapActions?.setMarkerA?.(place);
      mapActions?.flyTo?.(place);
    }
  }

  function setPointB(place) {
    pointB.value = place;
    mapActions?.clearResultMarkers?.();
    if (place) {
      mapActions?.setMarkerB?.(place);
      mapActions?.flyTo?.(place);
    }
  }

  watch([() => mapRef?.value, pointA, pointB], async () => {
    const mapVal = mapRef?.value;
    if (!mapVal) return;
    if (pointA.value && pointB.value) {
      const result = await drawRoute(mapVal, pointA.value, pointB.value);
      distance.value = result?.distance ?? 0;
      duration.value = result?.duration ?? 0;
    } else {
      clearRoute(mapVal);
      mapActions.clearResultMarkers?.();
      distance.value = 0;
      duration.value = 0;
    }
  }, { immediate: true });

  return { pointA, pointB, duration, distance, hasRouteInfo, setPointA, setPointB, displayPlace, formatDuration, formatDistance };
}
