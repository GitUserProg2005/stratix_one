<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    /** { active: boolean, days: number } */
    streak: { type: Object, required: true },
    /** ref контейнера чата (HTMLElement), в пределах которого перетаскиваем */
    containerRef: { type: Object, default: null },
});

const SIZE = 80;
/** Цель по дням для 100% (например, 30 дней) */
const TARGET_DAYS = 30;

const position = ref({ left: 340, top: 200 });
const dragging = ref(false);
const showModal = ref(false);
/** Было ли движение во время последнего pointer down → up (чтобы не открывать модалку при драге) */
let didMove = false;
/** Контейнер, зафиксированный на время перетаскивания */
let dragContainer = null;
let startX = 0;
let startY = 0;
let startLeft = 0;
let startTop = 0;

const progressPercent = computed(() => Math.min(100, Math.round((props.streak?.days ?? 0) / TARGET_DAYS * 100)));

function getClientCoords(e) {
    if (e.touches?.length) {
        return { x: e.touches[0].clientX, y: e.touches[0].clientY };
    }
    return { x: e.clientX, y: e.clientY };
}

/** Ограничение позиции видимой областью контейнера (viewport-координаты для fixed) */
function clampPosition(left, top) {
    const el = getContainer();
    if (!el) return { left: position.value.left, top: position.value.top };
    const rect = el.getBoundingClientRect();
    const minLeft = rect.left;
    const minTop = rect.top;
    const maxLeft = rect.right - SIZE;
    const maxTop = rect.bottom - SIZE;
    return {
        left: Math.max(minLeft, Math.min(left, maxLeft)),
        top: Math.max(minTop, Math.min(top, maxTop)),
    };
}

function getContainer() {
    return props.containerRef?.value ?? null;
}

function onPointerDown(e) {
    dragContainer = getContainer() ?? e.currentTarget?.parentElement;
    if (!dragContainer) return;
    didMove = false;
    const { x, y } = getClientCoords(e);
    startX = x;
    startY = y;
    startLeft = position.value.left;
    startTop = position.value.top;
    dragging.value = true;
    document.addEventListener('mousemove', onPointerMove);
    document.addEventListener('mouseup', onPointerUp);
    document.addEventListener('touchmove', onPointerMove, { passive: false });
    document.addEventListener('touchend', onPointerUp);
}

const DRAG_THRESHOLD = 6;

function onPointerMove(e) {
    if (!dragging.value || !dragContainer) return;
    const { x, y } = getClientCoords(e);
    if (Math.abs(x - startX) > DRAG_THRESHOLD || Math.abs(y - startY) > DRAG_THRESHOLD) {
        didMove = true;
    }
    e.preventDefault?.();
    const newLeft = startLeft + (x - startX);
    const newTop = startTop + (y - startY);
    const rect = dragContainer.getBoundingClientRect();
    const minLeft = rect.left;
    const minTop = rect.top;
    const maxLeft = rect.right - SIZE;
    const maxTop = rect.bottom - SIZE;
    position.value = {
        left: Math.max(minLeft, Math.min(newLeft, maxLeft)),
        top: Math.max(minTop, Math.min(newTop, maxTop)),
    };
}

function onPointerUp() {
    if (!didMove) {
        showModal.value = true;
    }
    dragging.value = false;
    dragContainer = null;
    document.removeEventListener('mousemove', onPointerMove);
    document.removeEventListener('mouseup', onPointerUp);
    document.removeEventListener('touchmove', onPointerMove);
    document.removeEventListener('touchend', onPointerUp);
}

function closeModal() {
    showModal.value = false;
}

onMounted(() => {
    nextTick(() => {
        const el = getContainer();
        if (el) {
            const rect = el.getBoundingClientRect();
            position.value = clampPosition(rect.right - SIZE - 16, rect.bottom - SIZE - 16);
        }
    });
});

onBeforeUnmount(() => {
    onPointerUp();
});
</script>

<template>
    <div
        v-if="streak?.active"
        class="fixed z-50 cursor-grab active:cursor-grabbing select-none touch-none pointer-events-auto"
        :style="{
            left: position.left + 'px',
            top: position.top + 'px',
            width: SIZE + 'px',
            height: SIZE + 'px',
        }"
        :title="`Огонёк: ${streak.days} дн.`"
        @mousedown.prevent.stop="onPointerDown"
        @touchstart.prevent.stop="onPointerDown"
    >
        <div
            class="w-full h-full flex flex-col items-center justify-center text-white font-bold text-sm pointer-events-none"
        >
            <img src="/img/fire.png" class="w-48" alt="">
        </div>
    </div>

    <Modal :show="showModal" @close="closeModal">
        <div class="bg-body p-6 rounded-lg text-white space-y-2">
            <h3 class="title text-lg mb-4">
                Какой-то даун</h3>
            
            <div class="flex items-center justify-center">
                <img src="/img/fire.png" class="w-32" alt="">
            </div>

            <p class="context text-sm mb-4">
                {{ streak.days }} {{ streak.days === 1 ? 'день' : streak.days < 5 ? 'дня' : 'дней' }} подряд
            </p>
            <div class="w-full h-5 rounded-full bg-white/10 overflow-hidden relative">
                <div
                    class="absolute inset-y-0 left-0 rounded-full transition-all duration-300 flex items-center justify-center min-w-[2.5rem]"
                    :style="{
                        width: progressPercent + '%',
                        background: 'linear-gradient(to right, #f97316, #ef4444)',
                    }"
                >
                    <span class="text-white font-bold text-sm drop-shadow-sm -rotate-12">
                        {{ progressPercent }}%
                    </span>
                </div>
            </div>
            <p class="context text-xs mt-2">{{ progressPercent }}% до цели ({{ TARGET_DAYS }} дн.)</p>
        </div>
    </Modal>
</template>
