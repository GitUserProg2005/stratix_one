<template>
  <div class="radial-visualizer relative w-full h-full flex items-center justify-center">
    <canvas ref="canvasRef" class="max-w-full max-h-full" :style="{ width: size + 'px', height: size + 'px' }" />
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  /** HTMLAudioElement или null — источник для анализа */
  audio: { type: Object, default: null },
  /** Размер canvas (px) */
  size: { type: Number, default: 280 },
  /** Базовый радиус круга (px) */
  baseRadius: { type: Number, default: 80 },
  /** Усиление амплитуды волны */
  gain: { type: Number, default: 1.5 },
  /** Цвет обводки (CSS) */
  strokeStyle: { type: String, default: 'rgba(255,255,255,0.8)' },
});

const canvasRef = ref(null);

let ctx = null;
let analyser = null;
let audioContext = null;
let source = null;
let dataArray = null;
let bufferLength = 0;
let animationId = null;

function initAnalyser() {
  if (!props.audio || !canvasRef.value) return;
  try {
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
    source = audioContext.createMediaElementSource(props.audio);
    analyser = audioContext.createAnalyser();
    analyser.fftSize = 256;
    analyser.smoothingTimeConstant = 0.8;
    source.connect(analyser);
    analyser.connect(audioContext.destination);
    bufferLength = analyser.frequencyBinCount;
    dataArray = new Uint8Array(bufferLength);
  } catch (e) {
    console.warn('RadialVisualizer: AudioContext failed', e);
  }
}

function draw() {
  if (!canvasRef.value || !ctx || !analyser || !dataArray) return;

  const canvas = canvasRef.value;
  const dpr = window.devicePixelRatio || 1;
  const w = props.size;
  const h = props.size;
  if (canvas.width !== w * dpr || canvas.height !== h * dpr) {
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width = w + 'px';
    canvas.style.height = h + 'px';
    ctx.scale(dpr, dpr);
  }

  analyser.getByteFrequencyData(dataArray);

  const cx = w / 2;
  const cy = h / 2;
  const baseR = props.baseRadius;
  const gainVal = props.gain;

  ctx.clearRect(0, 0, w, h);
  ctx.beginPath();

  const bins = Math.min(360, bufferLength);
  const step = 360 / bins;

  for (let i = 0; i <= 360; i += step) {
    const binIndex = Math.floor((i / 360) * bufferLength) % bufferLength;
    const amp = (dataArray[binIndex] / 255) * baseR * gainVal;
    const r = baseR + amp;
    const rad = (i * Math.PI) / 180;
    const x = cx + r * Math.cos(rad);
    const y = cy + r * Math.sin(rad);
    if (i === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  }

  ctx.closePath();
  ctx.strokeStyle = props.strokeStyle;
  ctx.lineWidth = 2;
  ctx.stroke();
  animationId = requestAnimationFrame(draw);
}

function start() {
  if (!props.audio || !canvasRef.value) return;
  initAnalyser();
  if (analyser) draw();
}

function stop() {
  if (animationId) {
    cancelAnimationFrame(animationId);
    animationId = null;
  }
  if (audioContext) {
    audioContext.close().catch(() => {});
    audioContext = null;
  }
  
  analyser = null;
  source = null;
  if (canvasRef.value && ctx) {
    ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
  }
}

watch(
  () => props.audio,
  (el) => {
    stop();
    if (el) {
      ctx = canvasRef.value?.getContext('2d');
      start();
    }
  },
  { immediate: true }
);

onMounted(() => {
  if (props.audio && canvasRef.value) {
    ctx = canvasRef.value.getContext('2d');
    start();
  }
});

onBeforeUnmount(stop);
</script>
