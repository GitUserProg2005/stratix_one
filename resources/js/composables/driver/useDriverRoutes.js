import { ref } from 'vue';
import { clearRoute, drawRoute } from '@/composables/useRouteLayer.js';

const ROUTE_TO_PICKUP = { sourceId: 'route-driver-pickup', layerId: 'route-line-driver-pickup', color: '#22c55e' };
const ROUTE_TO_DEST = { sourceId: 'route-pickup-dest', layerId: 'route-line-pickup-dest', color: '#3b82f6' };

export function useDriverRoutes({ mapRef, user }) {
  const distance = ref(0);
  const duration = ref(0);

  function clearRoutes() {
    const mapVal = mapRef?.value;
    if (!mapVal) return;
    clearRoute(mapVal, ROUTE_TO_PICKUP);
    clearRoute(mapVal, ROUTE_TO_DEST);
  }

  async function drawForOrder(order) {
    const mapVal = mapRef?.value;
    if (!mapVal || !order) return { distance: 0, duration: 0 };

    const pickup = { lat: Number(order.pickup_lat), lon: Number(order.pickup_lng), display_name: order.pickup_address ?? 'Pickup' };
    const dest = { lat: Number(order.destination_lat), lon: Number(order.destination_lng), display_name: order.destination_address ?? 'Destination' };
    clearRoutes();

    const u = user?.value ?? user;
    let driverPos = u?.lat != null && u?.lng != null ? { lat: Number(u.lat), lon: Number(u.lng), display_name: 'You' } : null;
    if (!driverPos && typeof navigator !== 'undefined' && navigator.geolocation) {
      try {
        const pos = await new Promise((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 5000, maximumAge: 60000 });
        });
        if (pos?.coords) driverPos = { lat: pos.coords.latitude, lon: pos.coords.longitude, display_name: 'You' };
      } catch {}
    }

    if (driverPos) {
      const result = await drawRoute(mapVal, driverPos, pickup, ROUTE_TO_PICKUP);
      distance.value = result?.distance ?? 0;
      duration.value = result?.duration ?? 0;
    } else {
      distance.value = 0;
      duration.value = 0;
    }

    await drawRoute(mapVal, pickup, dest, ROUTE_TO_DEST);
    return { distance: distance.value, duration: duration.value };
  }

  return { distance, duration, clearRoutes, drawForOrder };
}
