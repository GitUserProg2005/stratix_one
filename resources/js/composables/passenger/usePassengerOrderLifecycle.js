import { onBeforeUnmount, reactive } from 'vue';
import axios from 'axios';
import { clearRoute } from '@/composables/useRouteLayer.js';

export function usePassengerOrderLifecycle({ mapRef, mapActions = {} }) {
  const state = reactive({
    driverOnTheWay: false,
    orderTimeArrival: null,
    currentOrderId: null,
    driverInfo: null,
    orderPickupDuration: 0,
    orderPickupDistance: 0,
    orderStage: 'accepted',
    orderCanceled: false,
    orderCreating: false,
    orderError: null,
  });

  let orderChannel = null;

  function subscribeOrderChannel(orderId) {
    if (typeof window.Echo === 'undefined' || !orderId) return;
    if (orderChannel && state.currentOrderId === orderId) return;
    if (orderChannel && state.currentOrderId) {
      window.Echo.leave(`orders.${state.currentOrderId}`);
      orderChannel = null;
    }

    const ch = window.Echo.private(`orders.${orderId}`);
    ch.listen('.order.accepted', (payload) => {
      if (payload.driver) state.driverInfo = payload.driver;
      if (payload.order) {
        state.orderPickupDuration = payload.order.time_pickup ?? 0;
        state.orderPickupDistance = payload.order.distance_pickup ?? 0;
      }
      state.orderStage = 'accepted';
      state.driverOnTheWay = true;
    });
    ch.listen('.order.arrived', () => { state.orderStage = 'arrived'; });
    ch.listen('.order.in_way', () => { state.orderStage = 'in_way'; });
    ch.listen('.order.completed', () => { state.orderStage = 'completed'; });
    ch.listen('.order.canceled', () => {
      state.driverOnTheWay = false;
      state.orderStage = 'completed';
      state.currentOrderId = null;
      state.orderCanceled = true;
      setTimeout(() => { state.orderCanceled = false; }, 6000);
    });
    orderChannel = ch;
  }

  async function createOrder({ pointA, pointB, price, durationSeconds }) {
    if (!pointA || !pointB) {
      state.orderError = 'Выберите пункты А и Б';
      return;
    }
    state.orderError = null;
    state.orderCreating = true;
    try {
      const timeArrivalMinutes = durationSeconds > 0 ? Math.max(1, Math.ceil(durationSeconds / 60)) : 1;
      const { data } = await axios.post(route('order.create'), {
        point_a: { lat: pointA.lat, lng: pointA.lon ?? pointA.lng, display_name: pointA.display_name ?? null },
        point_b: { lat: pointB.lat, lng: pointB.lon ?? pointB.lng, display_name: pointB.display_name ?? null },
        price: price ? parseFloat(price) : null,
        time_arrival: timeArrivalMinutes,
      });
      mapActions?.fitBoundsToMarkers?.();
      if (data.success && data.order) {
        state.driverOnTheWay = true;
        state.orderTimeArrival = timeArrivalMinutes;
        state.currentOrderId = data.order.id;
        subscribeOrderChannel(data.order.id);
      }
    } catch (e) {
      state.orderError = e.response?.data?.message || 'Ошибка создания заказа';
    } finally {
      state.orderCreating = false;
    }
  }

  async function cancelOrder() {
    if (!state.currentOrderId) return;
    state.orderError = null;
    const id = state.currentOrderId;
    try {
      const { data } = await axios.post(route('order.cancel'), { order_id: id });
      if (!data.success) {
        state.orderError = data.message || 'Не удалось отменить заказ';
        return;
      }
      state.driverOnTheWay = false;
      state.driverInfo = null;
      state.orderStage = 'accepted';
      state.orderTimeArrival = null;
      state.currentOrderId = null;
      const mapVal = mapRef?.value;
      if (mapVal) {
        clearRoute(mapVal);
        mapActions?.clearResultMarkers?.();
      }
      state.orderCanceled = true;
      setTimeout(() => { state.orderCanceled = false; }, 6000);
      if (orderChannel && typeof window.Echo !== 'undefined') {
        window.Echo.leave(`orders.${id}`);
        orderChannel = null;
      }
    } catch (e) {
      state.orderError = e.response?.data?.message || 'Ошибка отмены заказа';
    }
  }

  onBeforeUnmount(() => {
    if (orderChannel && typeof window.Echo !== 'undefined' && state.currentOrderId) {
      window.Echo.leave(`orders.${state.currentOrderId}`);
      orderChannel = null;
    }
  });

  return { state, createOrder, cancelOrder, subscribeOrderChannel };
}
