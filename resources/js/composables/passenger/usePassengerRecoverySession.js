import { watch } from 'vue';
import { useRecoverOrder } from '@/composables/useRecoverOrder.js';

export function usePassengerRecoverySession({ mapRef, mapActions = {}, routePreview, order }) {
  const { order: recoveredOrder, driver: recoveredDriver, recoverDataOrder } = useRecoverOrder();
  let recoveryDone = false;

  watch(() => mapRef?.value, async (mapVal) => {
    if (!mapVal || recoveryDone) return;
    recoveryDone = true;

    try {
      await recoverDataOrder();
    } catch {
      return;
    }

    const o = recoveredOrder.value;
    if (!o?.id) return;
    const d = recoveredDriver.value;
    order.state.currentOrderId = o.id;

    let status = o.stage ?? o.status ?? 'accepted';
    if (status === 'in_progress') status = 'in_way';
    order.state.orderStage = status;
    order.state.driverOnTheWay = ['accepted', 'arrived', 'in_way'].includes(status);
    order.state.orderTimeArrival = (o.time_arrival ?? o.timeArrival ?? null) ?? (d?.duration ?? null);
    order.state.driverInfo = d ?? null;
    order.state.orderPickupDuration = o.time_pickup ?? 0;
    order.state.orderPickupDistance = o.distance_pickup ?? 0;

    if (o.pickup_lat != null && o.pickup_lng != null) {
      routePreview.pointA.value = { lat: Number(o.pickup_lat), lon: Number(o.pickup_lng), display_name: o.pickup_address ?? null };
      mapActions?.setMarkerA?.(routePreview.pointA.value);
    }
    if (o.destination_lat != null && o.destination_lng != null) {
      routePreview.pointB.value = { lat: Number(o.destination_lat), lon: Number(o.destination_lng), display_name: o.destination_address ?? null };
      mapActions?.setMarkerB?.(routePreview.pointB.value);
    }

    mapActions?.fitBoundsToMarkers?.();
    order.subscribeOrderChannel(o.id);
  }, { immediate: true });
}
