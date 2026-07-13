<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CreateMetric from './CreateMetric.vue';
import BackButton from '@/Components/BackButton.vue';
import NoAccess from '@/Components/NoAccess.vue';
import Rectangle from '@/Components/Skeleton/Rectangle.vue';

import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';

import { GridStack } from 'gridstack';
import 'gridstack/dist/gridstack.css';

import Chart from 'chart.js/auto';

import axios from 'axios';
import { pieColors } from '@/utils/charts/pie';

const props = defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
    has_access: {
        type: Boolean,
        default: true,
    },
    access_error: {
        type: String,
        default: null,
    },
});

const gridContainer = ref(null);
let grid = null;

const widgets = ref([]);
const isWidgetsLoading = ref(true);

const canvasByWidgetId = new Map();
const chartByWidgetId = new Map();

function setCanvasRef(widgetId, el) {
    if (!el) {
        canvasByWidgetId.delete(widgetId);
        return;
    }
    canvasByWidgetId.set(widgetId, el);
}

function destroyAllCharts() {
    for (const chart of chartByWidgetId.values()) {
        try {
            chart?.destroy?.();
        } catch {
            // noop
        }
    }
    chartByWidgetId.clear();
}

function toNumberArray(list) {
    return (Array.isArray(list) ? list : []).map((v) => {
        const n = Number(v);
        return Number.isFinite(n) ? n : 0;
    });
}

function renderCharts() {
    const aliveIds = new Set(widgets.value.map((w) => String(w.id)));

    for (const [id, chart] of chartByWidgetId.entries()) {
        if (!aliveIds.has(String(id))) {
            try {
                chart?.destroy?.();
            } catch {
                // noop
            }
            chartByWidgetId.delete(id);
        }
    }

    for (const w of widgets.value) {
        const canvas = canvasByWidgetId.get(w.id);
        if (!canvas) continue;

        const type = w.chartType || 'bar';
        const existing = chartByWidgetId.get(w.id);
        if (existing) {
            try {
                existing.data.labels = w.labels ?? [];
                existing.data.datasets[0].data = w.values ?? [];
                existing.update();
            } catch {
                try {
                    existing.destroy();
                } catch {
                    // noop
                }
                chartByWidgetId.delete(w.id);
            }
        }

        if (chartByWidgetId.has(w.id)) continue;

        const ctx = canvas.getContext('2d');
        if (!ctx) continue;

        const isPie = type === 'pie';

        chartByWidgetId.set(
            w.id,
            new Chart(ctx, {
                type,
                data: {
                    labels: w.labels ?? [],
                    datasets: [
                        {
                            label: w.title ?? 'Метрика',
                            data: w.values ?? [],
                            borderWidth: 1,
                            backgroundColor: isPie ? pieColors((w.labels ?? []).length) : 'rgba(233, 115, 88,0.28)',
                            borderColor: isPie ? 'rgba(0,0,0,0.12)' : 'rgba(233, 115, 88,0.75)',
                            borderRadius: isPie ? 0 : 10,
                            borderSkipped: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true },
                    },
                    layout: { padding: 6 },
                    elements: {
                        arc: {
                            spacing: isPie ? 4 : 0,
                            borderWidth: isPie ? 2 : 0,
                        },
                    },
                    scales: isPie
                        ? undefined
                        : {
                              x: { ticks: { font: { size: 11 } }, grid: { display: false } },
                              y: { beginAtZero: true, ticks: { font: { size: 11 } } },
                          },
                },
            }),
        );
    }
}

async function loadWidgets() {
    const showSkeleton = widgets.value.length === 0;
    if (showSkeleton) {
        isWidgetsLoading.value = true;
    }

    try {
        const { data } = await axios.get(route('dashboard.widgets', props.dashboard.id));

        widgets.value = (data ?? []).map((w) => {
            const position = w.position ?? {};
            const dataset = Array.isArray(w.content?.datasets) ? w.content.datasets[0] : null;
            const labels = Array.isArray(w.content?.labels) ? w.content.labels : [];
            const values = toNumberArray(dataset?.values);

            return {
                id: w.id,
                x: position.x ?? 0,
                y: position.y ?? 0,
                w: position.w ?? 2,
                h: position.h ?? 2,
                title: dataset?.title ?? 'Метрика',
                widgetType: w.type ?? null,
                chartType: dataset?.chart_type ?? null,
                labels,
                values,
            };
        });
    } catch (e) {
        console.error(e);
    } finally {
        if (showSkeleton) {
            isWidgetsLoading.value = false;
        }
    }
}

async function refreshGrid() {
    if (!gridContainer.value) return;

    if (grid) {
        grid.destroy(false);
        grid = null;
    }

    await nextTick();

    grid = GridStack.init(
        {
            column: 4,
            cellHeight: 220,
            margin: 12,
            float: true,
        },
        gridContainer.value,
    );

    grid.on('change', async (event, items) => {
        if (!items?.length) return;

        try {
            await Promise.all(
                items.map((item) =>
                    axios.post(route('dashboard.widgets.position', item.id), {
                        x: item.x,
                        y: item.y,
                        w: item.w,
                        h: item.h,
                    }),
                ),
            );
        } catch (e) {
            console.error('Не удалось сохранить позицию виджетов', e);
        }
    });
}

async function syncDashboardView() {
    if (isWidgetsLoading.value) {
        return;
    }

    await nextTick();
    await refreshGrid();
    await nextTick();
    renderCharts();
}

onMounted(async () => {
    if (!props.has_access) {
        isWidgetsLoading.value = false;
        return;
    }

    await loadWidgets();
});

watch(widgets, syncDashboardView, { deep: false });
watch(isWidgetsLoading, syncDashboardView);

onBeforeUnmount(() => {
    if (grid) {
        try {
            grid.destroy(false);
        } catch {
            // noop
        }
        grid = null;
    }
    destroyAllCharts();
});
</script>

<template>
    <Head :title="dashboard?.title || 'Дашборд'" />

    <DashboardLayout>
        <div class="p-4">
            <NoAccess v-if="!has_access && access_error" :title="access_error" />

            <template v-else>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <BackButton :backUrl="'dashboard'" />
                    <h2 class="title truncate">
                        {{ dashboard?.title || `Дашборд #${dashboard.id}` }}
                    </h2>
                </div>

                <div class="flex items-center gap-2">
                    <CreateMetric :dashboard-id="dashboard.id" @created="loadWidgets" />
                </div>
            </div>

            <section class="mt-4">
                <div
                    v-if="isWidgetsLoading"
                    class="grid grid-cols-1 gap-3 md:grid-cols-2"
                    aria-busy="true"
                    aria-label="Загрузка виджетов"
                >
                    <div
                        v-for="i in 4"
                        :key="i"
                        class="dashboard-card"
                    >
                        <Rectangle height="1rem" width="45%" rounded="rounded-md" />
                        <Rectangle class="mt-4" height="8.125rem" rounded="rounded-xl" />
                    </div>
                </div>

                <div v-else class="grid-stack min-h-[200px]" ref="gridContainer">
                    <div
                        v-for="w in widgets"
                        :key="w.id"
                        :gs-id="w.id"
                        :gs-x="w.x"
                        :gs-y="w.y"
                        :gs-w="w.w"
                        :gs-h="w.h"
                        class="grid-stack-item"
                    >
                        <div class="grid-stack-item-content dashboard-card">
                            <div class="context font-semibold truncate">
                                {{ w.title }}
                            </div>
                            <div class="mt-3 h-[130px]">
                                <canvas :ref="(el) => setCanvasRef(w.id, el)" class="w-full h-full" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            </template>
        </div>
    </DashboardLayout>
</template>

