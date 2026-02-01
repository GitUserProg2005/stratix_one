<template>
  <svg viewBox="0 0 100 20" preserveAspectRatio="none" class="w-full h-5 overflow-hidden">
    <path ref="wave1" fill="rgba(255,255,255,0.2)" />
    <path ref="wave2" fill="rgba(255,255,255,0.3)" />
  </svg>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { gsap } from 'gsap';

const wave1 = ref(null);
const wave2 = ref(null);

const points = 50; // количество точек на волне

function generateWavePath(phase = 0, amplitude = 4) {
  let path = `M0,10 `;
  for (let i = 0; i <= points; i++) {
    const x = (i / points) * 100;
    const y = 10 + Math.sin((i / points) * Math.PI * 2 + phase) * amplitude;
    path += `L${x},${y} `;
  }
  path += `L100,20 L0,20 Z`; // замыкаем путь снизу
  return path;
}

let animFrame;

function animateWave(pathRef, speed = 0.02, amplitude = 4) {
  let phase = 0;

  function tick() {
    phase += speed;
    if (pathRef.value) {
      pathRef.value.setAttribute('d', generateWavePath(phase, amplitude));
    }
    animFrame = requestAnimationFrame(tick);
  }

  tick();
}

onMounted(() => {
  animateWave(wave1, 0.02, 3); // первая волна
  animateWave(wave2, 0.01, 5); // вторая волна, чуть медленнее и выше
});

onBeforeUnmount(() => cancelAnimationFrame(animFrame));
</script>
