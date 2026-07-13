<script setup>
import Modal from '@/Components/Modal.vue';
import axios from 'axios';
import { computed, ref } from 'vue';

const emit = defineEmits(['created']);

const props = defineProps({
    dashboardId: {
        type: [Number, String],
        required: true,
    },
});

const showModal = ref(false);

function openModal() {
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

const title = ref('');
const selectedChartType = ref('bar');
const labelsInput = ref('');
const isDynamic = ref(true);

const chartTypes = {
    bar: { title: 'Столбчатый', icon: 'fa-chart-column' },
    line: { title: 'Линейный', icon: 'fa-chart-line' },
    pie: { title: 'Круговой', icon: 'fa-chart-pie' },
    doughnut: { title: 'Пончиковый', icon: 'fa-circle-notch' },
    polarArea: { title: 'Полярная область', icon: 'fa-bullseye' },
    radar: { title: 'Радар', icon: 'fa-spider' },
};

const canSubmit = computed(() => {
    return Boolean(title.value.trim()) && Boolean(selectedChartType.value) && Boolean(labelsInput.value.trim());
});

async function sendMetric() {
    if (!canSubmit.value) return;

    const labels = labelsInput.value
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean);

    if (!labels.length) return;

    try {
        await axios.post(route('dashboard.widgets.create', props.dashboardId), {
            title: title.value,
            chart_type: selectedChartType.value,
            labels,
            is_dynamic: isDynamic.value,
        });

        emit('created');
        title.value = '';
        labelsInput.value = '';
        selectedChartType.value = 'bar';
        isDynamic.value = true;
        closeModal();
    } catch (e) {
        console.error(e);
    }
}
</script>

<template>
    <button class="primary-btn" @click="openModal">
        Создать отчёт +
    </button>

    <Modal :show="showModal" @close="closeModal">
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="title-2">Создать метрику</h2>
                <button class="btn" @click="closeModal">
                    <i class="fa-solid fa-xmark" />
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2 md:col-span-1">
                    <label class="context text-sm">Название метрики</label>
                    <input
                        v-model="title"
                        type="text"
                        class="input w-full"
                        placeholder="Например, График продаж"
                    />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label class="context text-sm">Тип графика</label>
                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <button
                            v-for="(t, key) in chartTypes"
                            :key="key"
                            type="button"
                            class="bg-content-glass rounded-xl p-2 text-left border transition-colors sm:flex-1"
                            :class="selectedChartType === key ? 'ring-2 ring-accent' : 'border-transparent'"
                            @click="selectedChartType = key"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 rounded-xl bg-content-glass flex items-center justify-center">
                                    <i class="fa-solid" :class="t.icon" />
                                </div>
                                <span class="context">{{ t.title }}</span>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label class="context text-sm">Подписи (labels) через запятую</label>
                    <input
                        v-model="labelsInput"
                        type="text"
                        class="input w-full"
                        placeholder="Например: Январь, Февраль, Март"
                    />
                </div>

                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                        <input v-model="isDynamic" type="checkbox" class="checkbox" />
                        <span class="context">Динамическая метрика (значения будут обновляться)</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="closeModal">
                    Отмена
                </button>
                <button type="button" class="primary-btn" :disabled="!canSubmit" @click="sendMetric">
                    Создать
                </button>
            </div>
        </div>
    </Modal>
</template>