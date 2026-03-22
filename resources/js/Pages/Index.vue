<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import VerticalProgressBar from '@/Components/ProgressBars/VerticalProgressBar.vue';
import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Tooltip,
    Legend,
);

const chartCanvas = ref(null);
let chartInstance = null;
let themeObserver = null;

function isDarkTheme() {
    return document.documentElement.classList.contains('dark');
}

function getChartTheme() {
    const dark = isDarkTheme();
    return {
        legendColor: dark ? '#e5e7eb' : '#111827',
        tickColor: dark ? '#94a3b8' : '#6b7280',
        gridColor: dark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(17, 24, 39, 0.06)',
        pointBorder: dark ? '#1a1a1a' : '#ffffff',
    };
}

function buildChartOptions() {
    const t = getChartTheme();
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                labels: {
                    boxWidth: 10,
                    boxHeight: 10,
                    color: t.legendColor,
                    font: {
                        weight: '600',
                        size: 11,
                    },
                },
            },
            tooltip: {
                enabled: true,
                backgroundColor: isDarkTheme() ? '#f9fafb' : '#111827',
                titleColor: isDarkTheme() ? '#111827' : '#f9fafb',
                bodyColor: isDarkTheme() ? '#374151' : '#e5e7eb',
                titleFont: { size: 11, weight: '600' },
                bodyFont: { size: 11 },
                padding: 10,
                cornerRadius: 8,
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    color: t.tickColor,
                    font: { size: 11 },
                },
            },
            y: {
                grid: {
                    color: t.gridColor,
                },
                ticks: {
                    color: t.tickColor,
                    font: { size: 11 },
                    beginAtZero: true,
                },
            },
        },
    };
}

function applyChartTheme() {
    if (!chartInstance) return;
    const t = getChartTheme();
    chartInstance.options.plugins.legend.labels.color = t.legendColor;
    chartInstance.options.scales.x.ticks.color = t.tickColor;
    chartInstance.options.scales.y.ticks.color = t.tickColor;
    chartInstance.options.scales.y.grid.color = t.gridColor;
    chartInstance.data.datasets[0].pointBorderColor = t.pointBorder;
    const tip = chartInstance.options.plugins.tooltip;
    tip.backgroundColor = isDarkTheme() ? '#f9fafb' : '#111827';
    tip.titleColor = isDarkTheme() ? '#111827' : '#f9fafb';
    tip.bodyColor = isDarkTheme() ? '#374151' : '#e5e7eb';
    chartInstance.update();
}

function mountChart() {
    if (!chartCanvas.value) return;

    const ctx = chartCanvas.value.getContext('2d');
    const t = getChartTheme();

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'Completed',
                    data: [12, 16, 22, 30, 28, 34, 40],
                    borderColor: '#e97358',
                    backgroundColor: 'rgba(233, 115, 88, 0.1)',
                    tension: 0.5,
                    fill: true,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointBackgroundColor: '#e97358',
                    pointBorderColor: t.pointBorder,
                    pointBorderWidth: 2,
                },
            ],
        },
        options: buildChartOptions(),
        plugins: [
            {
                id: 'gradient-fill',
                beforeDraw(chart) {
                    const { ctx: c, chartArea } = chart;
                    if (!chartArea) return;

                    const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(233, 115, 88, 0.10)');
                    gradient.addColorStop(1, 'rgba(233, 115, 88, 0.00)');

                    const dataset = chart.data.datasets[0];
                    dataset.backgroundColor = gradient;
                },
            },
        ],
    });

    themeObserver = new MutationObserver(() => {
        applyChartTheme();
    });
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}

onMounted(() => {
    mountChart();
});

onBeforeUnmount(() => {
    themeObserver?.disconnect();
    themeObserver = null;
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
});
</script>

<template>
    <DashboardLayout>
        <div class="bg-content px-6 space-y-6">
            <!-- Top metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                <div class="content-dashboard flex flex-col gap-3">
                    <div class="flex items-start gap-4">
                        <div class="dashboard-icon-slot">
                            <i class="fa-solid fa-layer-group text-accent text-sm opacity-90" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="t-small font-semibold">Total Projects</h2>
                            <p class="context mt-1">Всего активных и завершённых</p>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="dashboard-stat-value">24</span>
                        <span class="badge-neutral">+8 this month</span>
                    </div>
                </div>

                <div class="content-dashboard flex flex-col gap-3">
                    <div class="flex items-start gap-4">
                        <div class="dashboard-icon-slot">
                            <i class="fa-solid fa-flag-checkered text-accent text-sm opacity-90" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="t-small font-semibold">Ended Projects</h2>
                            <p class="context mt-1">Закрыто за последний месяц</p>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="dashboard-stat-value">10</span>
                        <span class="badge-neutral">74% on time</span>
                    </div>
                </div>

                <div class="content-dashboard flex flex-col gap-3">
                    <div class="flex items-start gap-4">
                        <div class="dashboard-icon-slot">
                            <i class="fa-solid fa-play text-accent text-sm opacity-90" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="t-small font-semibold">Running Projects</h2>
                            <p class="context mt-1">Сейчас в активной разработке</p>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="dashboard-stat-value">12</span>
                        <span class="badge-neutral">5 due this week</span>
                    </div>
                </div>

                <div class="content-dashboard flex flex-col gap-3">
                    <div class="flex items-start gap-4">
                        <div class="dashboard-icon-slot">
                            <i class="fa-solid fa-circle-exclamation text-accent text-sm opacity-90" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="t-small font-semibold">Pending Projects</h2>
                            <p class="context mt-1">Ожидают решения или запуска</p>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="dashboard-stat-value">2</span>
                        <span class="badge-neutral">Need attention</span>
                    </div>
                </div>
            </div>

            <!-- Analytics + reminders -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="lg:col-span-2 content-dashboard">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="title-2">Project Analytics</h2>
                        <button type="button" class="primary-btn">View</button>
                    </div>

                    <div class="mt-5">
                        <div class="dashboard-chart-wrap">
                            <canvas ref="chartCanvas" />
                        </div>
                        <p class="context mt-3">
                            Динамика завершённых проектов по дням недели. Цвет графика синхронизирован с основным акцентом интерфейса.
                        </p>
                    </div>
                </div>

                <div class="content-dashboard">
                    <h2 class="title-2">Reminders</h2>
                    <div class="mt-4 space-y-3">
                        <div class="dashboard-inset">
                            <div class="dashboard-row-title">Meeting with Arc</div>
                            <div class="context">Time: 02:00 pm - 04:00 pm</div>
                            <button type="button" class="primary-btn mt-3 inline-flex items-center gap-2">
                                Start Meeting
                                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="content-dashboard">
                    <h2 class="title-2">Project</h2>
                    <div class="mt-4 space-y-3">
                        <div class="dashboard-inset">
                            <div class="dashboard-row-title">Develop API endpoints</div>
                            <div class="context">Due date: Nov 28, 2024</div>
                            <div class="mt-3 flex gap-2 flex-wrap">
                                <span class="tag">Onboarding</span>
                                <span class="tag">Build</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-dashboard">
                    <h2 class="title-2">Progresses</h2>
                    <div class="flex items-center mt-4 space-x-6">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <VerticalProgressBar
                                :value="40"
                                color="#e97358"
                                trackColor="#e97358"
                                :height="110"
                                :width="40"
                                :rounded="9999"
                                variant="hatch"
                            />

                            <h3 class="t-body font-semibold">Доход</h3>
                        </div>

                        <div class="flex flex-col items-center justify-center gap-2">
                            <VerticalProgressBar
                                :value="90"
                                color="#e97358"
                                trackColor="#e97358"
                                :height="110"
                                :width="40"
                                :rounded="9999"
                                variant="hatch"
                            />
                            <h3 class="title-2 text-sm">Расходы</h3>
                        </div>

                        <div class="flex flex-col items-center justify-center gap-2">
                            <VerticalProgressBar
                                :value="40"
                                color="#e97358"
                                trackColor="#e97358"
                                :height="110"
                                :width="40"
                                :rounded="9999"
                                variant="hatch"
                            />
                            <h3 class="t-body font-semibold">Бюджет</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom two columns -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 content-dashboard">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="title-2">Team Collaboration</h2>
                        <button type="button" class="tag">+ Add Member</button>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="dashboard-inset flex items-center gap-3">
                            <div class="dashboard-avatar dashboard-avatar--accent">
                                AD
                            </div>
                            <div class="min-w-0">
                                <div class="dashboard-row-title truncate">Alexandra Deff</div>
                                <div class="context truncate">Готовит релиз новой платёжной формы</div>
                            </div>
                            <span class="badge badge-in-progress ml-auto shrink-0">In Progress</span>
                        </div>
                        <div class="dashboard-inset flex items-center gap-3">
                            <div class="dashboard-avatar dashboard-avatar--sky">
                                DK
                            </div>
                            <div class="min-w-0">
                                <div class="dashboard-row-title truncate">Dima K.</div>
                                <div class="context truncate">Пилит realtime-дашборд и уведомления</div>
                            </div>
                            <span class="badge badge-completed ml-auto shrink-0">Completed</span>
                        </div>
                        <div class="dashboard-inset flex items-center gap-3">
                            <div class="dashboard-avatar dashboard-avatar--emerald">
                                MG
                            </div>
                            <div class="min-w-0">
                                <div class="dashboard-row-title truncate">Mary Green</div>
                                <div class="context truncate">Разрабатывает onboarding-экран и FAQ</div>
                            </div>
                            <span class="badge badge-pending ml-auto shrink-0">Pending</span>
                        </div>
                    </div>
                </div>

                <div class="content-dashboard">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="title-2">Project Progress</h2>
                        <button type="button" class="tag">Details</button>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="t-small font-medium">Mobile app redesign</span>
                                <span class="badge badge-in-progress">64%</span>
                            </div>
                            <div class="dashboard-progress-track">
                                <div class="dashboard-progress-fill dashboard-progress-fill--accent w-2/3" />
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="t-small font-medium">Billing integration</span>
                                <span class="badge badge-completed">100%</span>
                            </div>
                            <div class="dashboard-progress-track">
                                <div class="dashboard-progress-fill dashboard-progress-fill--success w-full" />
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="t-small font-medium">New landing page</span>
                                <span class="badge badge-pending">18%</span>
                            </div>
                            <div class="dashboard-progress-track">
                                <div class="dashboard-progress-fill dashboard-progress-fill--info w-1/5" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
