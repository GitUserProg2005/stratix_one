<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    tasks: {
        type: Array,
        required: true,
    },
});

const STATUS_ORDER = ['started', 'in_progress', 'review', 'completed'];

const STATUS_LABELS = {
    started: 'К выполнению',
    in_progress: 'В работе',
    review: 'На проверке',
    completed: 'Готово',
    canceled: 'Отменено',
    archived: 'Архив',
    finalized: 'Закрыто',
};

const PIE_COLORS = ['#fc7d61', '#93dd55', '#2ca04e', '#556cdd'];

const chartCanvas = ref(null);
let chartInstance = null;

const ratioStatuses = computed(() => {
    return props.tasks.reduce((acc, task) => {
        // Статус может прийти строкой или объектом enum
        const status = typeof task.status === 'object' ? task.status?.value : task.status;
        if (!status) return acc;

        if (!acc[status]) {
            acc[status] = 0;
        }

        acc[status]++;
        return acc;
    }, {});
});

const totalTasks = computed(() => props.tasks.length);

const orderedStatuses = computed(() => {
    const counts = ratioStatuses.value;
    const keys = Object.keys(counts);

    // Сначала колонки канбана, затем остальные статусы
    const ordered = STATUS_ORDER.filter((key) => counts[key]);
    const rest = keys.filter((key) => !STATUS_ORDER.includes(key));

    return [...ordered, ...rest];
});

const chartLabels = computed(() =>
    orderedStatuses.value.map((key) => STATUS_LABELS[key] ?? key),
);

const chartValues = computed(() =>
    orderedStatuses.value.map((key) => ratioStatuses.value[key]),
);

const chartColors = computed(() =>
    chartLabels.value.map((_, i) => PIE_COLORS[i % PIE_COLORS.length]),
);

function destroyChart() {
    if (!chartInstance) return;
    chartInstance.destroy();
    chartInstance = null;
}

function renderChart() {
    if (!chartCanvas.value) return;

    // Нет данных — убираем инстанс
    if (!chartValues.value.length) {
        destroyChart();
        return;
    }

    // Инстанс уже есть — обновляем данные
    if (chartInstance) {
        chartInstance.data.labels = chartLabels.value;
        chartInstance.data.datasets[0].data = chartValues.value;
        chartInstance.data.datasets[0].backgroundColor = chartColors.value;
        chartInstance.update();
        return;
    }

    // Создаём круговую диаграмму
    const ctx = chartCanvas.value.getContext('2d');
    if (!ctx) return;

    chartInstance = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: chartLabels.value,
            datasets: [
                {
                    data: chartValues.value,
                    backgroundColor: chartColors.value,
                    borderWidth: 2,
                    borderColor: 'transparent',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        padding: 12,
                        font: { size: 11 },
                    },
                },
                tooltip: { enabled: true },
            },
        },
    });
}

watch(
    () => props.tasks,
    async () => {
        await nextTick();
        renderChart();
    },
    { deep: true },
);

onMounted(async () => {
    await nextTick();
    renderChart();
});

onBeforeUnmount(() => {
    destroyChart();
});
</script>

<template>
    <div class="dashboard-inset mt-4 flex flex-col gap-3">
        <div class="flex items-center justify-between gap-2">
            <span class="title-3 shrink-0">Статусы</span>
            <span class="text-sm opacity-60">{{ totalTasks }}</span>
        </div>

        <div v-if="totalTasks" class="relative h-48 w-full">
            <canvas ref="chartCanvas" />
        </div>

        <p v-else class="text-sm opacity-50">Нет задач</p>
    </div>
</template>
