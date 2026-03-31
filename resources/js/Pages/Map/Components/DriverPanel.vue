<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import DriverOrderActions from './DriverOrderActions.vue';
import { useRecoverOrder } from '@/composables/useRecoverOrder.js';
import NotificationModal from './NotificationModal.vue';
import DriverTournamentPanel from './DriverTournamentPanel.vue';
import { useDriverRoutes } from '@/composables/driver/useDriverRoutes.js';
import { useDriverOrderSession } from '@/composables/driver/useDriverOrderSession.js';
import { useGeoCells } from '@/composables/useGeoCells.js';

const props = defineProps({
  map: { type: Object, default: null },
  user: { type: Object, default: null },
  driverLiveLat: { type: [Number, Object], default: null },
  driverLiveLng: { type: [Number, Object], default: null },
});

const notificationRef = ref(null);
const { order: recoveredOrder, recoverDataOrder } = useRecoverOrder();
const recoveryDone = ref(false);
const mapRef = computed(() => (props.map?.value ?? props.map));
const routes = useDriverRoutes({ mapRef, user: props.user });
const driverSession = useDriverOrderSession({ computePickupRoute: routes.drawForOrder, clearRoutes: routes.clearRoutes });
const { orders, currentOrder, arrivedAt, inWayAt, orderAccepted, orderCanceled, addOrder, removeOrder, acceptOrder, rejectOrder, onArrived, onInWay, onComplete, initFromRecoveredOrder, handleCanceledByPassenger } = driverSession;
const { updateCurrentCells, unsubscribeFromCells } = useGeoCells({ onOrderCreated: addOrder });

const driverLat = computed(() => props.driverLiveLat != null ? Number(props.driverLiveLat) : (props.user?.lat != null ? Number(props.user.lat) : null));
const driverLng = computed(() => props.driverLiveLng != null ? Number(props.driverLiveLng) : (props.user?.lng != null ? Number(props.user.lng) : null));
watch(() => [driverLat.value, driverLng.value], ([lat, lng]) => { if (lat != null && lng != null) updateCurrentCells(lat, lng); }, { immediate: true });

let currentOrderChannel = null;
function subscribeCurrentOrderChannel(orderId) {
  if (typeof window.Echo === 'undefined') return;
  if (currentOrderChannel && currentOrder.value?.order?.id) {
    const prevId = currentOrder.value.order.id;
    window.Echo.private(`orders.${prevId}`).stopListening('.order.canceled');
    window.Echo.leave(`orders.${prevId}`);
    currentOrderChannel = null;
  }
  const ch = window.Echo.private(`orders.${orderId}`);
  ch.listen('.order.canceled', () => {
    handleCanceledByPassenger();
    notificationRef.value.open('Заказ отменен');
  });
  currentOrderChannel = ch;
}

function formatCoords(lat, lng) { return lat == null || lng == null ? '—' : `${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)}`; }
function formatPlace(order, type) { return type === 'pickup' ? (order.pickup_address ?? formatCoords(order.pickup_lat, order.pickup_lng)) : (order.destination_address ?? formatCoords(order.destination_lat, order.destination_lng)); }

onMounted(async () => {
  if (recoveryDone.value) return;
  recoveryDone.value = true;
  try { await recoverDataOrder(); } catch { return; }
  const o = recoveredOrder.value;
  if (!o?.id) return;
  initFromRecoveredOrder(o, o.customer?.name ?? o.customer_name ?? 'Пассажир');
  removeOrder(o.id);
  subscribeCurrentOrderChannel(o.id);
  await routes.drawForOrder(o);
});

onBeforeUnmount(() => {
  routes.clearRoutes();
  unsubscribeFromCells();
  if (typeof window.Echo !== 'undefined' && currentOrderChannel && currentOrder.value?.order?.id) {
    const id = currentOrder.value.order.id;
    window.Echo.private(`orders.${id}`).stopListening('.order.canceled');
    window.Echo.leave(`orders.${id}`);
    currentOrderChannel = null;
  }
});
</script>

<template>
  <NotificationModal ref="notificationRef" />
  <aside class="fixed inset-x-0 bottom-0 z-10 max-h-[85vh] overflow-y-auto lg:relative lg:inset-auto lg:max-h-full lg:col-start-1 lg:row-start-2 lg:border-r lg:border-black/10 lg:dark:border-white/10 lg:bg-content">
    <div class="bg-content rounded-t-3xl lg:rounded-none shadow-lg border border-black/10 dark:border-white/10 border-b-0 lg:border-b lg:shadow-none flex flex-col min-h-0 lg:h-full">
      <div class="p-4 lg:p-6">
        <h2 class="title text-center mb-4">Новые заказы</h2>
        <DriverTournamentPanel :map="map" :user="user" />
        <div v-if="currentOrder" class="mb-6">
          <div class="bg-content-outline border border-black/10 dark:border-white/10 rounded-2xl p-4 shadow-sm">
            <p class="t-body font-semibold mb-1">Пассажир: {{ currentOrder.customer_name }}</p>
            <p class="context mb-2">Заказ #{{ currentOrder.order.id }}</p>
            <div class="space-y-1 t-small">
              <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>Откуда: {{ formatPlace(currentOrder.order, 'pickup') }}</div>
              <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>Куда: {{ formatPlace(currentOrder.order, 'dest') }}</div>
            </div>
            <DriverOrderActions :order-id="currentOrder.order.id" :arrived-at="arrivedAt" :in-way-at="inWayAt" :order-accepted="orderAccepted" :order-canceled="orderCanceled" @arrived="onArrived" @in-way="onInWay" @complete="onComplete" />
          </div>
        </div>
        <p v-if="!orders.length && !currentOrder" class="context text-center py-6">Ожидайте заказы. Они появятся здесь в реальном времени.</p>
        <div v-if="orders.length" class="space-y-3">
          <div v-for="item in orders" :key="item.id" class="bg-content-outline border border-black/10 dark:border-white/10 rounded-2xl p-4 shadow-sm">
            <div class="space-y-1.5 t-small">
              <div class="flex items-start gap-2"><span class="w-2.5 h-2.5 rounded-full bg-green-500 shrink-0 mt-0.5"></span><span>Откуда: {{ formatPlace(item.order, 'pickup') }}</span></div>
              <div class="flex items-start gap-2"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0 mt-0.5"></span><span>Куда: {{ formatPlace(item.order, 'dest') }}</span></div>
            </div>
            <div class="mt-3 flex gap-2">
              <button type="button" class="btn-primary-block flex-1" @click="acceptOrder(item)">Принять</button>
              <button type="button" class="primary-btn-white-blur w-full px-4 py-2 rounded-xl font-medium" @click="rejectOrder(item)">Отклонить</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>
