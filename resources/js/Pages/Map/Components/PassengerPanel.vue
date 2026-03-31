<script setup>
import { ref, computed, toRefs, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import MapSearchInput from './MapSearchInput.vue';
import OrderStatusStage from './OrderStatusStage.vue';
import { useSetSize } from '@/composables/useSetSize.js';
import { useRoutePreview } from '@/composables/passenger/useRoutePreview.js';
import { usePassengerOrderLifecycle } from '@/composables/passenger/usePassengerOrderLifecycle.js';
import { usePassengerRecoverySession } from '@/composables/passenger/usePassengerRecoverySession.js';

const props = defineProps({
  map: { type: Object, default: null },
  setMarkerA: { type: Function, default: () => {} },
  setMarkerB: { type: Function, default: () => {} },
  flyTo: { type: Function, default: () => {} },
  clearResultMarkers: { type: Function, default: () => {} },
  addResultMarkers: { type: Function, default: () => {} },
  fitBoundsToMarkers: { type: Function, default: () => {} },
});
const emit = defineEmits(['update:point-a-center']);
const asideContainer = ref(null);
const price = ref('');
const modalShow = ref(false);
const modalPoint = ref(null);
const { height, startResize } = useSetSize(asideContainer);

const mapRef = computed(() => (props.map?.value ?? props.map));
const mapActions = {
  setMarkerA: props.setMarkerA,
  setMarkerB: props.setMarkerB,
  flyTo: props.flyTo,
  clearResultMarkers: props.clearResultMarkers,
  addResultMarkers: props.addResultMarkers,
  fitBoundsToMarkers: props.fitBoundsToMarkers,
};

const routePreview = useRoutePreview({ mapRef, mapActions });
const { pointA, pointB, duration, distance, hasRouteInfo, setPointA, setPointB, displayPlace, formatDuration, formatDistance } = routePreview;
watch(pointA, (value) => {
  const lat = value?.lat ?? null;
  const lng = value?.lng ?? value?.lon ?? null;
  emit('update:point-a-center', lat != null && lng != null ? { lat: parseFloat(lat), lng: parseFloat(lng) } : null);
}, { immediate: true });

const order = usePassengerOrderLifecycle({ mapRef, mapActions });
const { driverOnTheWay, orderTimeArrival, currentOrderId, driverInfo, orderPickupDuration, orderStage, orderCanceled, orderCreating, orderError } = toRefs(order.state);
usePassengerRecoverySession({ mapRef, mapActions, routePreview, order });

function openModalFor(point) { modalPoint.value = point; modalShow.value = true; }
function closeModal() { modalShow.value = false; modalPoint.value = null; }
function onSearchResults(places) { props.clearResultMarkers(); if (places?.length) props.addResultMarkers(places); }
function onSelectPointA(place) { setPointA(place); closeModal(); }
function onSelectPointB(place) { setPointB(place); closeModal(); }
async function createOrder() { await order.createOrder({ pointA: pointA.value, pointB: pointB.value, price: price.value, durationSeconds: duration.value }); }
async function cancelOrder() { await order.cancelOrder(); }
</script>

<template>
  <aside ref="asideContainer" :style="height > 0 ? { '--aside-height': height + 'px' } : {}" :class="{ 'aside-resized-mobile': height > 0 }" class="fixed inset-x-0 bottom-0 z-10 max-h-[85vh] overflow-y-auto lg:relative lg:inset-auto lg:max-h-full lg:col-start-1 lg:row-start-2 lg:border-r lg:border-black/10 lg:dark:border-white/10 lg:bg-content">
    <div class="bg-content rounded-t-2xl lg:rounded-none shadow-lg border border-black/10 dark:border-white/10 border-b-0 lg:border-b-0 lg:shadow-none flex flex-col min-h-0 lg:h-full">
      <div class="mx-auto w-[80px] h-1 mt-3 bg-content-outline px-4 py-1 rounded-full cursor-row-resize touch-none lg:hidden" @mousedown="startResize" @touchstart.prevent="startResize" />
      <div class="p-4 lg:p-6">
        <h2 class="title text-center mb-4">Куда едем?</h2>
        <template v-if="driverOnTheWay">
          <OrderStatusStage :stage="orderStage" :time-pickup="orderPickupDuration" :order-time-arrival="orderTimeArrival" :driver-info="driverInfo" />
          <div v-if="!orderCanceled && orderStage !== 'completed'" class="mt-4 flex justify-center">
            <button type="button" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-red-500 text-red-500 rounded-lg text-sm font-medium hover:bg-red-500/10 transition-colors" @click="cancelOrder">Отменить заказ</button>
          </div>
        </template>
        <div v-else class="space-y-4">
          <button type="button" class="w-full text-left px-3 py-3 bg-content-outline border border-black/10 dark:border-white/10 rounded-lg t-body truncate" @click="openModalFor('A')"><span :class="{ context: !pointA }">{{ displayPlace(pointA, 'Откуда') }}</span></button>
          <button type="button" class="w-full text-left px-3 py-3 bg-content-outline border border-black/10 dark:border-white/10 rounded-lg t-body truncate" @click="openModalFor('B')"><span :class="{ context: !pointB }">{{ displayPlace(pointB, 'Куда') }}</span></button>
          <input v-model="price" type="number" min="0" step="0.01" placeholder="Цена" class="input w-full px-3 py-2 rounded-lg" />
          <div v-if="hasRouteInfo" class="grid grid-cols-2 gap-1">
            <div class="route-info-badge flex items-center justify-center"><span>{{ formatDuration(duration) }}</span></div>
            <div class="route-info-badge flex items-center justify-center"><span>{{ formatDistance(distance) }}</span></div>
          </div>
          <p v-if="orderError" class="text-sm text-red-500 mb-2">{{ orderError }}</p>
          <button type="button" class="btn-primary-block flex-1" :disabled="orderCreating || !pointA || !pointB || !hasRouteInfo" @click="createOrder">{{ orderCreating ? 'Секунду...' : 'Заказать' }}</button>
        </div>
      </div>
    </div>
  </aside>

  <Modal :show="modalShow" max-width="lg" @close="closeModal">
    <div class="p-4">
      <h3 class="title-2 mb-3">{{ modalPoint === 'A' ? 'Пункт А — Откуда' : 'Пункт Б — Куда' }}</h3>
      <MapSearchInput v-if="modalPoint === 'A'" key="search-a" v-model="pointA" placeholder="Введите адрес..." @update:model-value="onSelectPointA" @results="onSearchResults" />
      <MapSearchInput v-else-if="modalPoint === 'B'" key="search-b" v-model="pointB" placeholder="Введите адрес..." @update:model-value="onSelectPointB" @results="onSearchResults" />
    </div>
  </Modal>
</template>
