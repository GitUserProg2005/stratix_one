import { ref } from 'vue';
import axios from 'axios';

export function useDriverOrderSession({ computePickupRoute, clearRoutes }) {
  const orders = ref([]);
  const currentOrder = ref(null);
  const arrivedAt = ref(false);
  const inWayAt = ref(false);
  const orderAccepted = ref(false);
  const orderCanceled = ref(false);

  function addOrder(payload) {
    const { order, time_arrival, customer_name } = payload;
    orders.value = [{ order, time_arrival, customer_name: customer_name ?? null, id: order.id }, ...orders.value];
  }
  
  function removeOrder(orderId) {
    orders.value = orders.value.filter((item) => item.order.id !== orderId);
  }

  async function acceptOrder(item) {
    try {
      const { distance, duration } = (await computePickupRoute?.(item.order)) ?? { distance: 0, duration: 0 };
      const { data } = await axios.post(route('order.accept'), { order_id: item.order.id, distance, duration });
      if (data.success && data.order) {
        removeOrder(item.order.id);
        currentOrder.value = { order: data.order, customer_name: data.order.customer?.name ?? item.customer_name ?? 'Пассажир' };
        arrivedAt.value = false;
        inWayAt.value = false;
        orderAccepted.value = true;
      }
    } catch (e) {
      console.error('Accept order failed', e);
    }
  }

  function rejectOrder(item) { removeOrder(item.order.id); }
  function onArrived() { arrivedAt.value = true; }
  function onInWay() { inWayAt.value = true; }
  function onComplete() {
    clearRoutes?.();
    currentOrder.value = null;
    arrivedAt.value = false;
    inWayAt.value = false;
    orderAccepted.value = false;
  }

  function initFromRecoveredOrder(order, customerName) {
    currentOrder.value = { order, customer_name: customerName ?? order.customer?.name ?? order.customer_name ?? 'Пассажир' };
    const stage = order.stage ?? order.status ?? null;
    arrivedAt.value = stage === 'arrived';
    inWayAt.value = stage === 'in_way';
    orderAccepted.value = true;
  }

  function handleCanceledByPassenger() {
    clearRoutes?.();
    currentOrder.value = null;
    arrivedAt.value = false;
    inWayAt.value = false;
    orderAccepted.value = false;
    orderCanceled.value = true;
    setTimeout(() => { orderCanceled.value = false; }, 6000);
  }

  return {
    orders, currentOrder, arrivedAt, inWayAt, orderAccepted, orderCanceled,
    addOrder, removeOrder, acceptOrder, rejectOrder, onArrived, onInWay, onComplete,
    initFromRecoveredOrder, handleCanceledByPassenger,
  };
}
