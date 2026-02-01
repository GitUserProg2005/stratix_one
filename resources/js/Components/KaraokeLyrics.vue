<script setup>
import { ref, watch, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  audio: Object, // объект Audio, передаём из Player
  lyrics: {
    type: Array,
    required: true
  }
});

const currentTime = ref(0);
let interval = null;


// наблюдаем за audio.currentTime
onMounted(() => {
  if (props.audio) {
    interval = setInterval(() => {
      currentTime.value = props.audio.currentTime;
    }, 100); // обновляем каждые 100ms
  }
});


onBeforeUnmount(() => {
  clearInterval(interval);
});

// вычисляем текущую строку
const currentLineIndex = computed(() => {
  return props.lyrics.findIndex(
    line => currentTime.value >= line.start && currentTime.value <= line.end
  );
});
</script>

<template>
  <div class="karaoke h-32 overflow-y-hidden flex flex-col justify-center gap-1 text-white">
    <div 
      v-for="(line, index) in lyrics"
      :key="index"
      :class="{
        'text-green-400 font-bold text-lg': index === currentLineIndex,
        'text-gray-400': index !== currentLineIndex
      }"
    >
      {{ line.text }}
    </div>
  </div>
</template>