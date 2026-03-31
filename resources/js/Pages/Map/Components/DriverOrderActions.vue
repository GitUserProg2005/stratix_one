<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  orderId: { type: Number, default: null },
  arrivedAt: { type: Boolean, default: false },
  inWayAt: { type: Boolean, default: false },
});

const emit = defineEmits(['arrived', 'inWay', 'complete']);
const arrivedSending = ref(false);
const inWaySending = ref(false);
const completeSending = ref(false);

async function markArrived() {
  if (!props.orderId || arrivedSending.value || props.arrivedAt) return;
  arrivedSending.value = true;
  try {
    const { data } = await axios.post(route('order.arrived'), { order_id: props.orderId });
    if (data.success) emit('arrived');
  } finally {
    arrivedSending.value = false;
  }
}
async function markInWay() {
  if (!props.orderId || inWaySending.value || props.inWayAt) return;
  inWaySending.value = true;
  try {
    const { data } = await axios.post(route('order.in_way'), { order_id: props.orderId });
    if (data.success) emit('inWay');
  } finally {
    inWaySending.value = false;
  }
}
async function markComplete() {
  if (!props.orderId || completeSending.value) return;
  completeSending.value = true;
  try {
    const { data } = await axios.post(route('order.complete'), { order_id: props.orderId });
    if (data.success) emit('complete');
  } finally {
    completeSending.value = false;
  }
}
</script>

<template>
  <div class="mt-3">
    <button v-if="!arrivedAt" type="button" class="btn-primary-block w-full" :disabled="arrivedSending" @click="markArrived">
      {{ arrivedSending ? 'Секунду...' : 'Я на месте' }}
    </button>
    <template v-else>
      <button v-if="!inWayAt" type="button" class="btn-primary-block w-full" :disabled="inWaySending" @click="markInWay">
        {{ inWaySending ? 'Секунду...' : 'В пути' }}
      </button>
      <button v-else type="button" class="btn-primary-block w-full" :disabled="completeSending" @click="markComplete">
        {{ completeSending ? 'Секунду...' : 'Завершить' }}
      </button>
    </template>
  </div>
</template>
