<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import VerticalProgressBar from '@/Components/ProgressBars/VerticalProgressBar.vue';
</script>

<template>
    <DashboardLayout>
        <div class="space-y-6 content rounded-3xl">
            <!-- Top metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                <div class="content-dashboard">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="title-2">Total Projects</h2>
                            <p class="context mt-2">Увеличилось за последний месяц</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center">
                            <i class="fa-solid fa-arrow-up-right text-green-700" />
                        </div>
                    </div>
                    <div class="mt-4 text-4xl font-bold">24</div>
                </div>

                <div class="content-dashboard">
                    <h2 class="title-2">Ended Projects</h2>
                    <p class="context mt-2">Было закрыто за последний месяц</p>
                    <div class="mt-4 text-4xl font-bold">10</div>
                </div>

                <div class="content-dashboard">
                    <h2 class="title-2">Running Projects</h2>
                    <p class="context mt-2">Активно в работе</p>
                    <div class="mt-4 text-4xl font-bold">12</div>
                </div>

                <div class="content-dashboard">
                    <h2 class="title-2">Pending Projects</h2>
                    <p class="context mt-2">Требуют внимания</p>
                    <div class="mt-4 text-4xl font-bold">2</div>
                </div>
            </div>

            <!-- Analytics + reminders -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="lg:col-span-2 content-dashboard">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="title-2">Project Analytics</h2>
                        <button class="tag">View</button>
                    </div>

                    <div class="mt-5">
                        <div class="h-40 w-full rounded-2xl bg-gray-100 flex items-center justify-center">
                            <div class="text-gray-400 font-medium">Chart placeholder</div>
                        </div>
                        <p class="context mt-3">
                            Заглушка под график/диаграмму (вынесем в компонент позже).
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
                                color="#44c179"
                                trackColor="#44c179"
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
                                color="#54b5e3"
                                trackColor="#54b5e3"
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
                                U
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold truncate">Alexandra Deff</div>
                                <div class="context truncate">Working on Github Project Repository</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-dashboard">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="title-2">Project Progress</h2>
                        <button class="tag">Details</button>
                    </div>

                    <div class="mt-5 h-44 rounded-2xl bg-gray-100 flex items-center justify-center">
                        <div class="text-gray-400 font-medium">Progress placeholder</div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>