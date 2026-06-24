<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: undefined,
    },
    min: {
        type: Number,
        default: 0,
    },
    max: {
        type: Number,
        default: 100,
    },
    step: {
        type: [String, Number],
        default: 1,
    },
});

const emit = defineEmits(['update:modelValue']);

const currentValue = computed(() => {
    const raw = props.modelValue ?? props.min;
    const num = Number(raw);

    return Number.isFinite(num) ? num : props.min;
});

const displayValue = computed(() => {
    const step = String(props.step);
    const decimals = step.includes('.') ? step.split('.')[1].length : 0;

    return currentValue.value.toFixed(decimals);
});

const rangeStyle = computed(() => {
    const span = props.max - props.min || 1;
    const percent = ((currentValue.value - props.min) / span) * 100;

    return { '--range-progress': `${percent}%` };
});

function onInput(event) {
    emit('update:modelValue', event.target.value);
}
</script>

<template>
    <div class="range-field mt-2">
        <div class="range-field__header">
            <span class="t-mini">{{ min }}</span>
            <span class="range-field__value label-accent">{{ displayValue }}</span>
            <span class="t-mini">{{ max }}</span>
        </div>

        <input
            type="range"
            class="range-input"
            :style="rangeStyle"
            :value="currentValue"
            :min="min"
            :max="max"
            :step="step"
            @input="onInput"
        />
    </div>
</template>
