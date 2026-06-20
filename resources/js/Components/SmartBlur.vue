<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { FaceDetector, FilesetResolver } from "@mediapipe/tasks-vision"

const props = defineProps({
    modelValue: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const videoElement = ref(null);
let faceDetector = null;
let animationFrame = null;
let intervalId = null;

const initDetector = async () => {
    try {
        // Обязательно https://www.gstatic.com (с WWW) и полный путь до версии
        const vision = await FilesetResolver.forVisionTasks(
            "https://gstatic.com"
        );
        
        faceDetector = await FaceDetector.createFromOptions(vision, {
            baseOptions: {
                modelAssetPath: "https://googleapis.com",
                delegate: "GPU"
            },
            runningMode: "VIDEO"
        });

        console.log('✅ Детектор лиц готов');
        intervalId = setInterval(startDetection, 3000);
    }
    catch (error) {
        console.error('❌ Ошибка инициализации:', error);
    }
}


const startDetection = () => {
    if (!faceDetector || !videoElement.value) return;

    if (videoElement.value.readyState >= 2) {
        const startTimeMs = performance.now();
        const results = faceDetector.detectForVideo(videoElement.value, startTimeMs);
            
        console.log('ОБНАРУЖЕНО ЛИЦ: ', results.detections.length);

        const shouldBlur = results.detections.length > 1;
        if (shouldBlur !== props.modelValue) {
            emit('update:modelValue', shouldBlur);
        }
    }
}

onMounted(async () => {
  const stream = await navigator.mediaDevices.getUserMedia({ video: true });
  videoElement.value.srcObject = stream;
  await initDetector()
});

onUnmounted(() => {
  cancelAnimationFrame(animationFrame)
  if (videoElement.value.srcObject) {
    videoElement.value.srcObject.getTracks().forEach(track => track.stop())
  }
})
</script>

<template>
    <video ref="videoElement" class="hidden" autoplay playsinline></video>
</template>