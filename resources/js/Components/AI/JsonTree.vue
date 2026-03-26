<script setup>
import { computed, ref } from 'vue';
defineOptions({ name: 'JsonTree' });

const props = defineProps({
    value: {
        type: [Object, Array, String, Number, Boolean, null],
        required: true,
    },
    nodeKey: {
        type: String,
        default: '',
    },
    depth: {
        type: Number,
        default: 0,
    },
});

const isOpen = ref(true);

const isObject = computed(() => props.value !== null && typeof props.value === 'object');
const isArray = computed(() => Array.isArray(props.value));
const entries = computed(() => {
    if (!isObject.value) {
        return [];
    }

    return Object.entries(props.value);
});

const valueType = computed(() => {
    if (props.value === null) {
        return 'null';
    }

    if (isArray.value) {
        return 'array';
    }

    return typeof props.value;
});

const toggle = () => {
    if (!isObject.value) {
        return;
    }

    isOpen.value = !isOpen.value;
};
</script>

<template>
    <div class="text-sm">
        <div class="flex items-center gap-2">
            <button
                v-if="isObject"
                type="button"
                class="px-2 py-1 rounded bg-black/20 text-white text-xs"
                @click="toggle"
            >
                {{ isOpen ? '-' : '+' }}
            </button>

            <span class="font-semibold text-white/95">
                {{ nodeKey || 'root' }}
            </span>

            <span class="text-white/70 text-xs">
                ({{ valueType }})
            </span>

            <span
                v-if="!isObject"
                class="text-white break-all"
            >
                {{ String(value) }}
            </span>
        </div>

        <div
            v-if="isObject && isOpen"
            class="mt-2 space-y-2 border-l border-white/30 pl-3"
            :style="{ marginLeft: depth ? '0.25rem' : '0' }"
        >
            <JsonTree
                v-for="[childKey, childValue] in entries"
                :key="`${nodeKey}.${childKey}`"
                :value="childValue"
                :node-key="String(childKey)"
                :depth="depth + 1"
            />
        </div>
    </div>
</template>
