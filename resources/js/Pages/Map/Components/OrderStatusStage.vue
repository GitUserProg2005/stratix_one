<script setup>
import { onBeforeUnmount, ref, watch, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  stage: { type: String, default: 'accepted' },
  timePickup: { type: Number, default: null },
  orderTimeArrival: { type: Number, default: null },
  driverInfo: { type: Object, default: null },
});

const timeRemainingSeconds = ref((props.timePickup ?? ((props.orderTimeArrival ?? props.driverInfo?.duration ?? 0) || 0) * 60) || 0);
let timer = null;

watch(() => [props.timePickup, props.orderTimeArrival], ([tp, eta]) => {
  timeRemainingSeconds.value = tp != null && tp > 0 ? tp : (eta || 0) * 60;
  if (props.stage === 'accepted' && timeRemainingSeconds.value > 0) startTimer();
}, { immediate: true });

watch(() => props.stage, (stage) => {
  if (stage === 'accepted' && timeRemainingSeconds.value > 0) startTimer();
  else clearTimer();
}, { immediate: true });

const formattedEta = computed(() => {
  if (!timeRemainingSeconds.value || timeRemainingSeconds.value <= 0) return null;
  const total = Math.max(0, Math.floor(timeRemainingSeconds.value));
  const minutes = Math.floor(total / 60);
  const seconds = total % 60;
  return `${minutes} мин ${seconds.toString().padStart(2, '0')} сек`;
});

function startTimer() {
  if (timer || timeRemainingSeconds.value <= 0) return;
  timer = setInterval(() => {
    if (timeRemainingSeconds.value > 0) timeRemainingSeconds.value -= 1;
    else clearTimer();
  }, 1000);
}
function clearTimer() {
  if (!timer) return;
  clearInterval(timer);
  timer = null;
}
onBeforeUnmount(clearTimer);
</script>

<template>
  <div class="space-y-4">
    <template v-if="stage === 'accepted'">
      <p class="t-small text-center">
        Водитель в пути
        <span v-if="formattedEta" class="block mt-1 t-body font-medium">~{{ formattedEta }}</span>
      </p>
    </template>
    <template v-else-if="stage === 'arrived'">
      <div class="bg-content-outline border border-black/10 dark:border-white/10 rounded-2xl p-4 shadow-sm text-center"><p class="t-body font-semibold">Водитель прибыл</p></div>
    </template>
    <template v-else-if="stage === 'in_way'">
      <div class="bg-content-outline border border-black/10 dark:border-white/10 rounded-2xl p-4 shadow-sm text-center"><p class="t-body font-semibold">В пути</p></div>
    </template>
    <template v-else-if="stage === 'completed'">
      <div class="bg-content-outline border border-black/10 dark:border-white/10 rounded-2xl p-4 shadow-sm text-center">
        <p class="t-body font-semibold">Вы прибыли</p>
        <Link :href="route('map')" class="primary-btn inline-flex items-center justify-center w-10 h-10 p-0 rounded-full mt-3">
          <i class="fa-solid fa-arrow-left text-sm text-white"></i>
        </Link>
      </div>
    </template>
  </div>
</template>
