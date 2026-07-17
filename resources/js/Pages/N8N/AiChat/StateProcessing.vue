<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    roomId: {
        type: [Number, String],
        required: true,
    },
    isProcessing: {
        type: Boolean,
        default: false,
    },
});

const currentState = ref('Обрабатываю...');
const isBroken = ref(false);

let echoChannel = null;

function leaveChannel() {
    if (typeof window.Echo === 'undefined' || !props.roomId) {
        return;
    }

    const name = `ai-chat-states.${props.roomId}`;
    try {
        window.Echo.leave(`private-${name}`);
    } catch (_) {
        window.Echo.leave(name);
    }
    echoChannel = null;
}

function subscribeToStateChanges() {
    if (typeof window.Echo === 'undefined' || !props.roomId) {
        return;
    }

    leaveChannel();

    echoChannel = window.Echo.private(`ai-chat-states.${props.roomId}`)
        .listen('StatusProcessingUpdated', (e) => {
            if (typeof e?.thoughts === 'string' && e.thoughts !== '') {
                currentState.value = e.thoughts;
            }
            isBroken.value = e?.is_broken === true;
        });
}

watch(() => props.roomId, () => {
    currentState.value = 'Обрабатываю...';
    isBroken.value = false;
    subscribeToStateChanges();
});

watch(() => props.isProcessing, (on) => {
    if (on) {
        currentState.value = 'Обрабатываю...';
        isBroken.value = false;
    }
});

onMounted(() => {
    subscribeToStateChanges();
});

onBeforeUnmount(() => {
    leaveChannel();
});
</script>

<template>
    <div
        v-if="isProcessing || isBroken"
        class="flex w-full justify-start"
        aria-live="polite"
        :aria-busy="isProcessing && !isBroken"
    >
        <div
            class="relative max-w-[82%] overflow-hidden rounded-2xl rounded-bl-sm px-3 py-3"
            :class="isBroken ? 'ai-thought-broken' : 'bg-content-glass'"
        >
            <div
                v-if="!isBroken"
                class="pointer-events-none absolute inset-0 overflow-hidden"
                aria-hidden="true"
            >
                <div
                    class="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-r from-transparent via-white/25 to-transparent dark:via-white/10 animate-shimmer-slide"
                />
            </div>
            <div
                class="relative flex items-center gap-2.5 text-sm"
                :class="isBroken ? 't-color-danger' : 'text-[var(--content-primary)]'"
            >
                <i
                    v-if="isBroken"
                    class="fa-solid fa-xmark shrink-0 text-base t-color-danger"
                    aria-hidden="true"
                />
                <i
                    v-else
                    class="fa-solid fa-spinner fa-spin shrink-0 text-base text-[var(--accent)]"
                    aria-hidden="true"
                />
                <span class="flex items-center gap-2">
                    <div
                        v-if="!isBroken"
                        class="w-2.5 h-3 bg-[#00c853] animate-pulse [clip-path:polygon(50%_0%,_100%_25%,_100%_75%,_50%_100%,_0%_75%,_0%_25%)]"
                    />
                    {{ currentState }}
                </span>
            </div>
        </div>
    </div>
</template>
