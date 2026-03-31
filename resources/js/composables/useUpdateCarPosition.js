import axios from 'axios';
import { onUnmounted, ref } from 'vue';
import maplibregl from 'maplibre-gl';

export function useUpdateCarPosition({ mapRef, user_id, onDriverStale } = {}) {
  const driverMarkers = ref(new Map());
  const driverAnimations = new Map();
  const driverLastUpdate = new Map();
  let updatePositionInterval = null;
  let staleCheckInterval = null;
  const testDriverIntervals = [];

  const testDriversRoutes = [
    {
      driver_id: 5,
      coords: [
        { lat: 55.7383841, lng: 37.6184344 },
        { lat: 55.8281841, lng: 37.6184344 },
        { lat: 55.7379555, lng: 37.6161818 },
      ],
    },
  ];

  function resolveMap(maybeMap) {
    return (maybeMap?.value ?? maybeMap) || (mapRef?.value ?? mapRef) || null;
  }

  function touchDriver(driverId) {
    if (driverId != null) driverLastUpdate.set(driverId, Date.now());
  }

  function startStaleWatcher() {
    if (staleCheckInterval) return;
    staleCheckInterval = setInterval(() => {
      const now = Date.now();
      for (const [driverId, lastTs] of driverLastUpdate.entries()) {
        if (now - lastTs > 5000) {
          clearDriverMarker(driverId);
          driverLastUpdate.delete(driverId);
          if (typeof onDriverStale === 'function') onDriverStale(driverId);
        }
      }
    }, 10000);
  }

  function startTestDrivers(onMove) {
    testDriverIntervals.forEach((id) => clearInterval(id));
    testDriverIntervals.length = 0;
    testDriversRoutes.forEach((route) => {
      let index = 0;
      const id = setInterval(() => {
        const point = route.coords[index];
        onMove({ id: route.driver_id, lat: point.lat, lng: point.lng });
        index = (index + 1) % route.coords.length;
      }, 6000);
      testDriverIntervals.push(id);
    });
    return () => {
      testDriverIntervals.forEach((id) => clearInterval(id));
      testDriverIntervals.length = 0;
    };
  }

  let lastLat = null;
  let lastLng = null;
  function updatePosition(lat, lng) {
    lastLat = lat;
    lastLng = lng;
    if (updatePositionInterval) return;
    updatePositionInterval = setInterval(async () => {
      if (lastLat == null || lastLng == null) return;
      try {
        await axios.post(route('update.position'), { user_id, lat: lastLat, lng: lastLng });
      } catch {}
    }, 6000);
  }

  function createDriverMarkerElement() {
    const el = document.createElement('div');
    el.className = 'map-marker-car';
    el.style.width = '14px';
    el.style.height = '50px';
    el.style.backgroundImage = 'url(/img/car.png)';
    el.style.backgroundSize = 'cover';
    el.style.backgroundPosition = 'center';
    el.style.backgroundRepeat = 'no-repeat';
    return el;
  }

  function ensureDriverMarker(driverId, mapInstance, lat, lng) {
    const m = resolveMap(mapInstance);
    if (!m || driverId == null || Number.isNaN(lat) || Number.isNaN(lng)) return null;
    let marker = driverMarkers.value.get(driverId);
    if (!marker) {
      marker = new maplibregl.Marker({ element: createDriverMarkerElement() }).setLngLat([lng, lat]).addTo(m);
      driverMarkers.value.set(driverId, marker);
    }
    return marker;
  }

  async function drawDriverMarker(driver, mapInstance) {
    const driverId = driver?.driver_id ?? driver?.driver?.id ?? driver?.id;
    const lon = parseFloat(driver?.lon ?? driver?.lng);
    const driverLat = parseFloat(driver?.lat);
    if (!driver) return null;
    touchDriver(driverId);
    startStaleWatcher();
    return ensureDriverMarker(driverId, mapInstance, driverLat, lon);
  }

  function clearDriverMarker(driverId) {
    const cancel = driverAnimations.get(driverId);
    if (cancel) {
      cancel();
      driverAnimations.delete(driverId);
    }
    const marker = driverMarkers.value.get(driverId);
    if (marker) {
      marker.remove();
      driverMarkers.value.delete(driverId);
    }
  }

  function driverMovementAnimation(driverId, toCoords, mapInstance) {
    const toLat = Array.isArray(toCoords) ? parseFloat(toCoords[0]) : parseFloat(toCoords?.lat);
    const toLng = Array.isArray(toCoords) ? parseFloat(toCoords[1]) : parseFloat(toCoords?.lng);
    if (Number.isNaN(toLat) || Number.isNaN(toLng)) return;
    touchDriver(driverId);
    startStaleWatcher();
    const marker = ensureDriverMarker(driverId, mapInstance, toLat, toLng);
    if (!marker) return;
    const cancelPrev = driverAnimations.get(driverId);
    if (cancelPrev) cancelPrev();
    let cancelled = false;
    const cancel = () => { cancelled = true; };
    driverAnimations.set(driverId, cancel);
    const start = marker.getLngLat();
    const duration = 2000;
    const startTime = performance.now();
    function animate(time) {
      if (cancelled) {
        driverAnimations.delete(driverId);
        return;
      }
      const progress = Math.min(1, (time - startTime) / duration);
      marker.setLngLat([
        start.lng + (toLng - start.lng) * progress,
        start.lat + (toLat - start.lat) * progress,
      ]);
      if (progress < 1) requestAnimationFrame(animate);
      else driverAnimations.delete(driverId);
    }
    requestAnimationFrame(animate);
  }

  async function clearDriverMarkers() {
    driverAnimations.forEach((cancel) => cancel());
    driverAnimations.clear();
    driverMarkers.value.forEach((marker) => marker.remove());
    driverMarkers.value.clear();
    driverLastUpdate.clear();
  }

  onUnmounted(() => {
    testDriverIntervals.forEach((id) => clearInterval(id));
    if (updatePositionInterval) clearInterval(updatePositionInterval);
    if (staleCheckInterval) clearInterval(staleCheckInterval);
    driverAnimations.forEach((cancel) => cancel());
    driverMarkers.value.forEach((marker) => marker.remove());
    driverAnimations.clear();
    driverMarkers.value.clear();
    driverLastUpdate.clear();
  });

  return { startTestDrivers, updatePosition, drawDriverMarker, clearDriverMarker, clearDriverMarkers, driverMovementAnimation };
}
