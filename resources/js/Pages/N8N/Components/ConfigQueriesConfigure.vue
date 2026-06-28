<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import Rectangle from '@/Components/Skeleton/Rectangle.vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    nodeId: {
        type: [Number, String],
        required: true,
    },
    workflowId: {
        type: Number,
        default: null,
    },
    onSave: {
        type: Function,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const metrics = ref([]);
const isLoading = ref(false);

const selectedWidgetId = ref(null);
const selectedLabel = ref('');
const amount = ref(1);

const widgetOptions = computed(() => {
    return (metrics.value || []).map((m) => ({
        label: m.title,
        value: m.id,
    }));
});

const selectedMetric = computed(() => {
    return (metrics.value || []).find((m) => Number(m.id) === Number(selectedWidgetId.value)) ?? null;
});

const labelOptions = computed(() => {
    const labels = selectedMetric.value?.labels;
    if (!Array.isArray(labels)) return [];
    return labels.map((l) => ({ label: String(l), value: String(l) }));
});

const rows = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));

async function loadMetrics() {
    if (!props.workflowId) return;
    isLoading.value = true;
    try {
        const { data } = await axios.get(route('get.metrics', props.workflowId));
        metrics.value = Array.isArray(data?.metrics) ? data.metrics : [];
    } catch (e) {
        console.error('Не удалось загрузить метрики', e);
        metrics.value = [];
    } finally {
        isLoading.value = false;
    }
}

watch(
    () => props.workflowId,
    () => {
        loadMetrics();
    },
    { immediate: true },
);

watch(selectedWidgetId, () => {
    selectedLabel.value = '';
});

function addRow() {
    if (!selectedWidgetId.value || !selectedLabel.value) return;
    const n = Number(amount.value);
    const row = {
        widget_id: Number(selectedWidgetId.value),
        label: String(selectedLabel.value),
        amount: Number.isFinite(n) ? n : 0,
    };
    emit('update:modelValue', [...rows.value, row]);
    amount.value = 1;
}

function removeRow(index) {
    const next = rows.value.filter((_, i) => i !== index);
    emit('update:modelValue', next);
}

function updateAmount(index, value) {
    const n = Number(value);
    const next = rows.value.map((r, i) =>
        i === index ? { ...r, amount: Number.isFinite(n) ? n : 0 } : r,
    );
    emit('update:modelValue', next);
}
</script>

<template>
    <div class="mt-3 space-y-3">
        <div class="dashboard-inset p-3 space-y-3">
            <div v-if="isLoading" class="grid grid-cols-1 gap-3 md:grid-cols-3" aria-busy="true" aria-label="Загрузка метрик">
                <div v-for="i in 3" :key="i" class="md:col-span-1 space-y-2">
                    <Rectangle height="0.875rem" width="4rem" rounded="rounded-md" />
                    <Rectangle height="2.5rem" rounded="rounded-xl" />
                </div>
            </div>

            <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="md:col-span-1">
                    <div class="dashboard-row-title text-sm">Виджет</div>
                    <HeadlessSelect
                        v-model="selectedWidgetId"
                        :options="widgetOptions"
                        button-class="select-input mt-2 w-full"
                        placeholder="Выберите виджет"
                    />
                </div>

                <div class="md:col-span-1">
                    <div class="dashboard-row-title text-sm">Label</div>
                    <HeadlessSelect
                        v-model="selectedLabel"
                        :options="labelOptions"
                        :disabled="!selectedWidgetId"
                        button-class="select-input mt-2 w-full"
                        placeholder="Выберите label"
                    />
                </div>

                <div class="md:col-span-1">
                    <div class="dashboard-row-title text-sm">Amount</div>
                    <input
                        v-model="amount"
                        type="number"
                        class="input mt-2 w-full"
                        placeholder="1"
                    />
                </div>
            </div>

            <div class="flex items-center justify-between gap-2">
                <button type="button" class="primary-btn" :disabled="!selectedWidgetId || !selectedLabel" @click="addRow">
                    Добавить
                </button>

                <button v-if="onSave" type="button" class="btn-secondary" @click="onSave()">
                    Сохранить в ноду
                </button>
            </div>
        </div>

        <div v-if="rows.length" class="space-y-2">
            <div
                v-for="(r, idx) in rows"
                :key="`${r.widget_id}-${r.label}-${idx}`"
                class="content-glass rounded-xl p-3 flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
            >
                <div class="context text-sm">
                    <span class="opacity-70">widget:</span> {{ r.widget_id }}
                    <span class="opacity-70 ml-2">label:</span> {{ r.label }}
                </div>

                <div class="flex items-center gap-2">
                    <input
                        :value="r.amount"
                        type="number"
                        class="input w-28"
                        @input="updateAmount(idx, $event.target.value)"
                    />
                    <button type="button" class="btn" @click="removeRow(idx)">
                        <i class="fa-solid fa-trash" />
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="context text-sm opacity-70">
            Добавь хотя бы одну метрику для обновления
        </div>
    </div>
</template>

