<script setup>
import { computed, onMounted } from 'vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({}),
    },
    field: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['update:modelValue']);

const modeKey = computed(() => props.field.name || 'mode');

const currentMode = computed({
    get: () => props.modelValue[modeKey.value] ?? props.field.default ?? '',
    set: (value) => {
        emit('update:modelValue', {
            ...props.modelValue,
            [modeKey.value]: value,
        });
    },
});

const visibleFields = computed(() => {
    const mode = currentMode.value;

    return (props.field.fields || []).filter((item) => {
        if (!item.displayIf) {
            return true;
        }

        return Object.entries(item.displayIf).every(([key, value]) => {
            if (key === modeKey.value) {
                return mode === value;
            }

            return props.modelValue[key] === value;
        });
    });
});

function fieldKey(item, index) {
    return item.name || item.key || `field-${index}`;
}

onMounted(() => {
    if (!props.modelValue[modeKey.value] && props.field.default) {
        currentMode.value = props.field.default;
    }
});
</script>

<template>
    <div class="mt-2 space-y-3">
        <HeadlessSelect
            v-model="currentMode"
            :options="field.options || []"
            button-class="select-input w-full"
            :placeholder="field.label"
        />

        <div
            v-for="(item, index) in visibleFields"
            :key="fieldKey(item, index)"
            class="space-y-1"
        >
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="dashboard-row-title text-sm">{{ item.label }}</h4>
                <span v-if="item.from_input" class="badge badge-in-progress">Входные данные</span>
            </div>

            <input
                class="input w-full"
                type="text"
                :placeholder="item.label"
                disabled
            />
        </div>
    </div>
</template>
