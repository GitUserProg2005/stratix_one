<script setup>
import { ref, watch, computed, onMounted, onBeforeUnmount } from 'vue';
import Modal from './Modal.vue';

const props = defineProps({
  audio: Object,
  lyrics: {
    type: Array,
    required: true
  }
});

console.log('LYRICS; ', props.lyrics);

const showModal = ref(false);
const currentTime = ref(0);
let interval = null;

onMounted(() => {
  if (!props.audio) return;

  interval = setInterval(() => {
    if (!props.audio.paused) {
      currentTime.value = props.audio.currentTime;
    }
  }, 100);
});

onBeforeUnmount(() => {
  clearInterval(interval);
});

function toggleModal() {
  showModal.value = !showModal.value;
}

// вычисляем текущую строку
const currentLineIndex = computed(() => {
  if (!props.audio || !props.lyrics.length) return -1;

  const time = currentTime.value;

  // ищем последнюю строку, которая уже началась
  for (let i = props.lyrics.length - 1; i >= 0; i--) {
    if (time >= Number(props.lyrics[i].start_time)) {
      return i;
    }
  }

  return -1;
});

function lineClass(index) {
  if (index === currentLineIndex.value) {
    return 'text-green-400 text-xl font-semibold scale-105';
  }

  if (Math.abs(index - currentLineIndex.value) === 1) {
    return 'text-gray-300 opacity-70';
  }

  return 'text-gray-500 opacity-40';
}

</script>

<template>
  <button @click="toggleModal">
    <i class="fa-solid fa-music"></i>
  </button>

  <Modal v-model:show="showModal">
  <div class="bg-body h-80 px-6 py-4 relative overflow-hidden flex flex-col">

    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-white font-semibold">Караоке</h3>
      <button
        @click="toggleModal"
        class="text-gray-400 hover:text-white transition"
      >
        <i class="fa-solid fa-xmark text-xl"></i>
      </button>
    </div>

    <!-- Karaoke viewport -->
    <div class="relative flex-1 overflow-hidden">

      <!-- Центрирующая линия (невидимая) -->
      <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="h-px w-full"></div>
      </div>

      <!-- Lyrics list -->
      <div
        class="absolute left-0 right-0 transition-transform duration-500 ease-out"
        :style="{
          transform: `translateY(${50 - currentLineIndex * 32}%)`
        }"
      >
        <div
          v-for="(line, index) in lyrics"
          :key="index"
          class="h-8 flex items-center justify-center text-center transition-all duration-300"
          :class="lineClass(index)"
        >
          {{ line.line }}
        </div>
      </div>

    </div>
  </div>
</Modal>

</template>
