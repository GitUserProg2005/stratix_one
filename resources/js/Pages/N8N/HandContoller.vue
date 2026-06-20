<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Hands } from '@mediapipe/hands';
import { useVueFlow } from '@vue-flow/core';
import {
    drawConnectors,
    drawLandmarks,
} from '@mediapipe/drawing_utils';

const props = defineProps({
    vueFlowId: {
        type: String,
        required: true,
    },
});

const { findNode, project, updateNode } = useVueFlow(props.vueFlowId);

const handsData = ref([]); // [{ x, y, isPinching, label }]
const videoElement = ref(null);
const canvasElement = ref(null);

/** Гистерезис: иначе dist около порога даёт дрожание pinch on/off. */
const PINCH_ON = 0.038;
const PINCH_OFF = 0.058;

/** EMA для экранных координат (индекс палец дрожит от кадра к кадру). */
const SMOOTH_ALPHA = 0.22;

const smoothScreenByLabel = new Map(); // label -> { x, y }
const pinchActiveByLabel = new Map(); // label -> boolean
/** При щипке: смещение захвата, чтобы нода не прыгала (не фикс. -50/-20). */
const activeDragByLabel = new Map(); // label -> { nodeId, offsetX, offsetY }

let stream = null;
let rafId = null;
let hands = null;

function getNodeIdFromPoint(x, y) {
    const el = document.elementFromPoint(x, y);
    if (!el) return null;

    const nodeEl = el.closest?.('[data-id]');
    const id = nodeEl?.getAttribute?.('data-id');
    return id ? String(id) : null;
}

function smoothScreen(label, x, y) {
    const prev = smoothScreenByLabel.get(label);
    if (!prev) {
        const p = { x, y };
        smoothScreenByLabel.set(label, p);
        return p;
    }

    prev.x = prev.x * (1 - SMOOTH_ALPHA) + x * SMOOTH_ALPHA;
    prev.y = prev.y * (1 - SMOOTH_ALPHA) + y * SMOOTH_ALPHA;
    
    return prev;
}

function pinchHysteresis(label, dist) {
    const was = pinchActiveByLabel.get(label) ?? false;
    if (!was) {
        const on = dist < PINCH_ON;
        pinchActiveByLabel.set(label, on);
        return on;
    }
    if (dist > PINCH_OFF) {
        pinchActiveByLabel.set(label, false);
        return false;
    }
    return true;
}

function onResults(results) {
    const next = [];
    const seenLabels = new Set();

    const canvas = canvasElement.value;
    const video = videoElement.value;
    const hasLandmarks = results?.multiHandLandmarks?.length;

    // Подготовка canvas один раз на кадр, иначе 2-я рука затирает 1-ю
    let ctx = null;
    if (canvas && video) {
        ctx = canvas.getContext('2d');
        if (ctx) {
            const w = video.videoWidth || canvas.width;
            const h = video.videoHeight || canvas.height;
            if (w && h && (canvas.width !== w || canvas.height !== h)) {
                canvas.width = w;
                canvas.height = h;
            }
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    if (hasLandmarks) {
        results.multiHandLandmarks.forEach((landmarks, index) => {
            const label = results?.multiHandedness?.[index]?.label || String(index);
            seenLabels.add(label);

            const thumb = landmarks[4];
            const indexFinger = landmarks[8];

            if (ctx) {
                drawConnectors(ctx, landmarks, Hands.HAND_CONNECTIONS, {
                    color: '#00FF88',
                    lineWidth: 3,
                });

                drawLandmarks(ctx, landmarks, {
                    color: '#FF0044',
                    lineWidth: 2,
                    radius: 4,
                });
            }

            const dist = Math.hypot(
                thumb.x - indexFinger.x,
                thumb.y - indexFinger.y,
                thumb.z - indexFinger.z
            );

            const isPinching = pinchHysteresis(label, dist);

            const rawScreenX = (1 - indexFinger.x) * window.innerWidth;
            const rawScreenY = indexFinger.y * window.innerHeight;
            const { x: screenX, y: screenY } = smoothScreen(label, rawScreenX, rawScreenY);
            const flowPos = project({ x: screenX, y: screenY });

            if (isPinching) {
                if (!activeDragByLabel.has(label)) {
                    const nodeId = getNodeIdFromPoint(screenX, screenY);
                    const node = nodeId ? findNode(nodeId) : null;

                    if (node) {
                        activeDragByLabel.set(label, {
                            nodeId,
                            offsetX: node.position.x - flowPos.x,
                            offsetY: node.position.y - flowPos.y,
                        });
                    }
                } else {
                    const drag = activeDragByLabel.get(label);
                    const node = drag ? findNode(drag.nodeId) : null;
                    if (drag && node) {
                        updateNode(drag.nodeId, {
                            position: {
                                x: flowPos.x + drag.offsetX,
                                y: flowPos.y + drag.offsetY,
                            },
                        });
                    } else if (drag) {
                        activeDragByLabel.delete(label);
                    }
                }
            } else {
                activeDragByLabel.delete(label);
            }

            next.push({ x: screenX, y: screenY, isPinching, label });
        });
    }

    for (const key of smoothScreenByLabel.keys()) {
        if (!seenLabels.has(key)) smoothScreenByLabel.delete(key);
    }
    for (const key of pinchActiveByLabel.keys()) {
        if (!seenLabels.has(key)) pinchActiveByLabel.delete(key);
    }
    for (const key of activeDragByLabel.keys()) {
        if (!seenLabels.has(key)) activeDragByLabel.delete(key);
    }

    handsData.value = next;
}

async function startCameraAndHands() {
    hands = new Hands({
        locateFile: (file) =>
            `https://cdn.jsdelivr.net/npm/@mediapipe/hands/${file}`,
    });

    hands.setOptions({
        maxNumHands: 2,
        modelComplexity: 1,
        minDetectionConfidence: 0.7,
        minTrackingConfidence: 0.7,
    });

    hands.onResults(onResults);

    stream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: 'user',
        },
        audio: false,
    });

    if (!videoElement.value) return;
    videoElement.value.srcObject = stream;

    await new Promise((resolve) => {
        const v = videoElement.value;
        const done = () => resolve();
        if (v.readyState >= 2) return done();
        v.onloadedmetadata = done;
    });

    try {
        await videoElement.value.play?.();
    } catch {
        // autoplay might be blocked; user gesture will start it
    }

    const loop = async () => {
        if (!hands || !videoElement.value) return;
        try {
            await hands.send({ image: videoElement.value });
        } catch {
            // ignore intermittent frame errors
        }
        rafId = requestAnimationFrame(loop);
    };

    rafId = requestAnimationFrame(loop);
}

function stop() {
    if (rafId) cancelAnimationFrame(rafId);
    rafId = null;

    smoothScreenByLabel.clear();
    pinchActiveByLabel.clear();
    activeDragByLabel.clear();
    handsData.value = [];

    if (stream) {
        for (const track of stream.getTracks()) track.stop();
        stream = null;
    }

    if (videoElement.value) {
        videoElement.value.srcObject = null;
    }

    try {
        hands?.close?.();
    } catch {
        /* noop */
    }
    hands = null;
}

onMounted(() => {
    startCameraAndHands().catch((e) => {
        console.error('Hands camera init failed:', e);
    });
});

onBeforeUnmount(() => {
    stop();
});

</script>

<template>
    <div class="hand-video">
    <div class="flex flex-col gap-2">
        <div class="p-2">
            <div v-if="!handsData.length" class="text-red-500 text-center">
                Руки не обнаружены :(
            </div>
            <div v-else>
                <ul>
                    <li
                        :class="{
                            'text-green-500': handsData[0]?.isPinching,
                            'text-red-500': !handsData[0]?.isPinching,
                        }"
                    >
                        Рука 1: {{ handsData[0]?.isPinching ? 'Захват' : 'Не активна' }}
                    </li>
                    <li
                        v-if="handsData[1]"
                        :class="{
                            'text-green-500': handsData[1]?.isPinching,
                            'text-red-500': !handsData[1]?.isPinching,
                        }"
                    >
                        Рука 2: {{ handsData[1]?.isPinching ? 'Захват' : 'Не активна' }}
                    </li>
                </ul>
            </div>
        </div>
        <div class="relative">
            <video ref="videoElement" class="" autoplay playsinline muted></video>
            <canvas ref="canvasElement" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>
        </div>
    </div>
    </div>

    <div
        v-for="hand in handsData"
        :key="hand.label"
        class="hand-cursor"
        :class="{ pinching: hand.isPinching }"
        :style="{ left: hand.x + 'px', top: hand.y + 'px' }"
    >
        <div class="label">{{ hand.label === 'Left' ? 'L' : 'R' }}</div>
    </div>
</template>

<style scoped>
.hand-video {
    position: fixed;
    right: 50px;
    bottom: 110px;
    width: 260px;
    height: 200px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
    z-index: 9998;
    background: rgba(0, 0, 0, 0.35);
}

.hand-cursor {
    position: fixed;
    width: 30px;
    height: 30px;
    border: 3px solid #00eeff;
    border-radius: 50%;
    pointer-events: none;
    z-index: 9999;
    transform: translate(-50%, -50%);
    /* transition на transform усиливает визуальное «дрожание» позиции */
    transition: border-color 0.2s ease, background-color 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hand-cursor.pinching {
    border-color: #ff0072;
    transform: translate(-50%, -50%) scale(0.85);
    background: rgba(255, 0, 114, 0.2);
}

.label {
    font-size: 10px;
    color: white;
    font-weight: bold;
}
</style>