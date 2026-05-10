<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

import { Hands } from '@mediapipe/hands';
import {
    drawConnectors,
    drawLandmarks,
} from '@mediapipe/drawing_utils';

const pointers = ref([]);
const virtualPointers = new Map();

const videoElement = ref(null);
const canvasElement = ref(null);

/** Гистерезис: иначе dist около порога даёт дрожание pinch on/off. */
const PINCH_ON = 0.038;
const PINCH_OFF = 0.058;

/** EMA для экранных координат (индекс палец дрожит от кадра к кадру). */
const SMOOTH_ALPHA = 0.22;

const smoothScreenByLabel = new Map(); // label -> { x, y }
const pinchActiveByLabel = new Map(); // label -> boolean

let stream = null;
let rafId = null;
let hands = null;

class VirtualPointer {
    constructor(id, label) {
        this.id = id;
        this.label = label;

        this.x = 0;
        this.y = 0;

        this.isDown = false;
    }

    move(x, y) {
        if (
            Math.abs(this.x - x) < 5 && 
            Math.abs(this.y - y) < 5
        ) return;

        this.x = x;
        this.y = y;

        const target = document.elementFromPoint(x, y);
        if (!target) return;
        
        target.dispatchEvent(new PointerEvent('pointermove', {
            bubbles: true,
            cancelable: true,
            composed: true,
            pointerId: this.id,
            pointerType: 'pen',
            isPrimary: this.id === 1,
            clientX: x,
            clientY: y,
        }));

        target.dispatchEvent(new MouseEvent('mousemove', {
            bubbles: true,
            clientX: x,
            clientY: y,
        }));
    }

    down(x, y) {
        if (this.isDown) return; 

        this.isDown = true;

        const target = document.elementFromPoint(x, y);
        if (!target) return;

        this.downX = x;
        this.downY = y;

        target.dispatchEvent(new PointerEvent('pointerdown', {
            bubbles: true,
            clientX: x,
            clientY: y,
            pointerId: this.id,
        }));

        target.dispatchEvent(new MouseEvent('mousedown', {
            bubbles: true,
            clientX: x,
            clientY: y,
        }));

        if (
            target instanceof HTMLElement &&
            (
                target.tagName === 'INPUT' ||
                target.tagName === 'TEXTAREA' ||
                target.contentEditable === 'true'
            )
        ) {
            target.focus();
        }
    }

    up(x, y) {
        if (!this.isDown) return;

        this.isDown = false;

        const target = document.elementFromPoint(x, y);

        target.dispatchEvent(new PointerEvent('pointerup', {
            bubbles: true,
            clientX: x,
            clientY: y,
            pointerId: this.id,
        }));

        target.dispatchEvent(new MouseEvent('mouseup', {
            bubbles: true,
            clientX: x,
            clientY: y,
        }));  
        
        const moved = Math.hypot(x - this.downX, y - this.downY);

        if (moved < 10) {
            target.dispatchEvent(new MouseEvent('click', {
                bubbles: true,
                clientX: x,
                clientY: y,
            }));
        }
    }
}

function getPointer(label) {
    if (!virtualPointers.has(label)) {
        virtualPointers.set(label, new VirtualPointer( 
            label === 'Left' ? 1 : 2,
            label
        ));
    }

    return virtualPointers.get(label);
}

function smooth(label, x, y) {
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

function pinch(label, dist) {
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
    const nextPointers = [];
    const seenLabels = new Set();

    // Очищаем pointers, если нет рук
    if (!results.multiHandLandmarks?.length) {
        pointers.value = [];
        return;
    }

    // Get canvas and video
    const canvas = canvasElement.value;
    const video = videoElement.value;
    const hasLandmarks = results?.multiHandLandmarks?.length;

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
            // Get labels
            const label = results?.multiHandedness?.[index]?.label || String(index);
            seenLabels.add(label);

            // Landmarks
            const thumb = landmarks[4];
            const indexFinger = landmarks[8];

            // is pinching
            const dist = Math.hypot(
                thumb.x - indexFinger.x,
                thumb.y - indexFinger.y,
                thumb.z - indexFinger.z
            );
            const isPinching = pinch(label, dist);

            // Canvas drawing
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
            };
            
            // Pointer initialize
            const pointer = getPointer(label);

            // Smooth new coordinates
            const rawScreenX = (1 - indexFinger.x) * window.innerWidth;
            const rawScreenY = indexFinger.y * window.innerHeight;
            const { x: screenX, y: screenY } = smooth(label, rawScreenX, rawScreenY);

            // Move pointer
            pointer.move(screenX, screenY);

            // Down pointer 
            if (isPinching && !pointer.isDown) {
                pointer.down(screenX, screenY);
            };

            // Up pointer
            if (!isPinching && pointer.isDown) {
                pointer.up(screenX, screenY);
            }

            // Задаем структуру для pointers
            nextPointers.push({
                label,
                x: screenX,
                y: screenY,
                isPinching,
            });
        });
    }

    for (const [label, pointer] of virtualPointers) {
        if (!seenLabels.has(label)) {
            if (pointer.isDown) {
                pointer.up(pointer.x, pointer.y);
            }

            virtualPointers.delete(label);
            smoothScreenByLabel.delete(label);
            pinchActiveByLabel.delete(label);
        }
    }

    pointers.value = nextPointers;
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
    virtualPointers.clear();

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
            <div v-if="!pointers.length" class="text-red-500 text-center">
                Руки не обнаружены :(
            </div>
            <div v-else>
                <ul>
                    <li
                        :class="{
                            'text-green-500': pointers[0]?.isPinching,
                            'text-red-500': !pointers[0]?.isPinching,
                        }"
                    >
                        Рука 1: {{ pointers[0]?.isPinching ? 'Захват' : 'Не активна' }}
                    </li>
                    <li
                        v-if="pointers[1]"
                        :class="{
                            'text-green-500': pointers[1]?.isPinching,
                            'text-red-500': !pointers[1]?.isPinching,
                        }"
                    >
                        Рука 2: {{ pointers[1]?.isPinching ? 'Захват' : 'Не активна' }}
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
        v-for="pointer in pointers"
        :key="pointer.label"
        class="hand-cursor"
        :class="{ pinching: pointer.isPinching }"
        :style="{ left: pointer.x + 'px', top: pointer.y + 'px' }"
    >
        <div class="label">{{ pointer.label === 'Left' ? 'L' : 'R' }}</div>
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