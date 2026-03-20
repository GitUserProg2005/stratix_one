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

onMounted(() => {
    if (!chartCanvas.value) return;

    const ctx = chartCanvas.value.getContext('2d');

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
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        color: '#111827',
                        font: {
                            weight: '600',
                            size: 11,
                        },
                    },
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: '#111827',
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
                        color: '#6b7280',
                        font: { size: 11 },
                    },
                },
                y: {
                    grid: {
                        color: 'rgba(17, 24, 39, 0.06)',
                    },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 11 },
                        beginAtZero: true,
                    },
                },
            },
        },
        plugins: [
            {
                id: 'gradient-fill',
                beforeDraw(chart) {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;

                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(233, 115, 88, 0.10)');
                    gradient.addColorStop(1, 'rgba(233, 115, 88, 0.00)');

                    const dataset = chart.data.datasets[0];
                    dataset.backgroundColor = gradient;
                },
            },
        ],
    });
});

onBeforeUnmount(() => {
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
                        <div class="flex items-center justify-center">
                            <i class="fa-solid fa-layer-group text-[#e97358] text-sm opacity-90" />
                        </div>
                        <div>
                            <h2 class="t-small text-gray-600 font-semibold">Total Projects</h2>
                            <p class="context mt-1">Всего активных и завершённых</p>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-5xl font-extrabold tracking-tight">24</span>
                        <span class="badge border-transparent bg-black/5 text-[#1a1a1a]">+8 this month</span>
                    </div>
                </div>

                <div class="content-dashboard flex flex-col gap-3">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center">
                            <i class="fa-solid fa-flag-checkered text-[#e97358] text-sm opacity-90" />
                        </div>
                        <div>
                            <h2 class="t-small text-gray-600 font-semibold">Ended Projects</h2>
                            <p class="context mt-1">Закрыто за последний месяц</p>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-5xl font-extrabold tracking-tight">10</span>
                        <span class="badge border-transparent bg-black/5 text-[#1a1a1a]">74% on time</span>
                    </div>
                </div>

                <div class="content-dashboard flex flex-col gap-3">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center">
                            <i class="fa-solid fa-play text-[#e97358] text-sm opacity-90" />
                        </div>
                        <div>
                            <h2 class="t-small text-gray-600 font-semibold">Running Projects</h2>
                            <p class="context mt-1">Сейчас в активной разработке</p>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-5xl font-extrabold tracking-tight">12</span>
                        <span class="badge border-transparent bg-black/5 text-[#1a1a1a]">5 due this week</span>
                    </div>
                </div>

                <div class="content-dashboard flex flex-col gap-3">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center">
                            <i class="fa-solid fa-circle-exclamation text-[#e97358] text-sm opacity-90" />
                        </div>
                        <div>
                            <h2 class="t-small text-gray-600 font-semibold">Pending Projects</h2>
                            <p class="context mt-1">Ожидают решения или запуска</p>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-5xl font-extrabold tracking-tight">2</span>
                        <span class="badge border-transparent bg-black/5 text-[#1a1a1a]">Need attention</span>
                    </div>
                </div>
            </div>

            <!-- Analytics + reminders -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="lg:col-span-2 content-dashboard">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="title-2">Project Analytics</h2>
                        <button class="primary-btn">View</button>
                    </div>

                    <div class="mt-5">
                        <div class="h-56 w-full rounded-2xl bg-white border border-black/5 p-4">
                            <canvas ref="chartCanvas"></canvas>
                        </div>
                        <p class="context mt-3">
                            Динамика завершённых проектов по дням недели. Цвет графика синхронизирован с основным акцентом интерфейса.
                        </p>
                    </div>
                </div>

                <div class="content-dashboard">
                    <h2 class="title-2">Reminders</h2>
                    <div class="mt-4 space-y-3">
                        <div class="p-3 rounded-2xl bg-white/70 border border-black/5">
                            <div class="font-semibold">Meeting with Arc</div>
                            <div class="context">Time: 02:00 pm - 04:00 pm</div>
                            <button class="primary-btn mt-3 inline-flex items-center gap-2">
                                Start Meeting
                                <i class="fa-solid fa-arrow-right text-xs" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="content-dashboard">
                    <h2 class="title-2">Project</h2>
                    <div class="mt-4 space-y-3">
                        <div class="p-3 rounded-2xl bg-white/70 border border-black/5">
                            <div class="font-semibold">Develop API endpoints</div>
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

                            <h3>Доход</h3>
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
                            <h3>Расходы</h3>
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
                            <h3>Бюджет</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom two columns -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 content-dashboard">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="title-2">Team Collaboration</h2>
                        <button class="tag">+ Add Member</button>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/70 border border-black/5">
                            <div class="w-9 h-9 rounded-full bg-orange-400 flex items-center justify-center text-black font-bold">
                                AD
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold truncate">Alexandra Deff</div>
                                <div class="context truncate">Готовит релиз новой платёжной формы</div>
                            </div>
                            <span class="badge badge-in-progress ml-auto">In Progress</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/70 border border-black/5">
                            <div class="w-9 h-9 rounded-full bg-sky-400 flex items-center justify-center text-black font-bold">
                                DK
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold truncate">Dima K.</div>
                                <div class="context truncate">Пилит realtime-дашборд и уведомления</div>
                            </div>
                            <span class="badge badge-completed ml-auto">Completed</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/70 border border-black/5">
                            <div class="w-9 h-9 rounded-full bg-emerald-400 flex items-center justify-center text-black font-bold">
                                MG
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold truncate">Mary Green</div>
                                <div class="context truncate">Разрабатывает onboarding-экран и FAQ</div>
                            </div>
                            <span class="badge badge-pending ml-auto">Pending</span>
                        </div>
                    </div>
                </div>

                <div class="content-dashboard">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="title-2">Project Progress</h2>
                        <button class="tag">Details</button>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="t-small text-gray-700">Mobile app redesign</span>
                                <span class="badge badge-in-progress">64%</span>
                            </div>
                            <div class="mt-2 h-2 w-full rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full w-2/3 bg-[#e97358]" />
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="t-small text-gray-700">Billing integration</span>
                                <span class="badge badge-completed">100%</span>
                            </div>
                            <div class="mt-2 h-2 w-full rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full w-full bg-emerald-500" />
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="t-small text-gray-700">New landing page</span>
                                <span class="badge badge-pending">18%</span>
                            </div>
                            <div class="mt-2 h-2 w-full rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full w-1/5 bg-sky-500" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>