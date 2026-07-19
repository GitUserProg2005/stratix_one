<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CreateTask from '@/Pages/Task/Create.vue';

defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
    projects: {
        type: Array,
        default: () => [],
    },
});

const columns = [
    {
        title: 'К выполнению',
        color: '#ef4444',
        tasks: [
            { id: 1, title: 'Настроить окружение для разработки' },
            { id: 2, title: 'Реализовать авторизацию через Google' },
            { id: 3, title: 'Добавить компонент уведомлений' },
            { id: 4, title: 'Подготовить документацию по API' },
        ],
    },
    {
        title: 'В работе',
        color: '#3b82f6',
        tasks: [
            { id: 5, title: 'Реализовать доску задач (канбан)' },
            { id: 6, title: 'Интеграция с сервисом уведомлений' },
            { id: 7, title: 'Адаптивная вёрстка для мобильных' },
            { id: 8, title: 'Тестирование авторизации' },
            { id: 9, title: 'Тестирование авторизации' },
            { id: 10, title: 'Тестирование авторизации' },
            { id: 11, title: 'Тестирование авторизации' },
            { id: 12, title: 'Тестирование авторизации' },
            { id: 13, title: 'Тестирование авторизации' },
            { id: 13, title: 'Тестирование авторизации' },
            { id: 13, title: 'Тестирование авторизации' },
        ],
    },
    {
        title: 'На проверке',
        color: '#f59e0b',
        tasks: [
            { id: 9, title: 'Сортировка и фильтрация задач' },
            { id: 10, title: 'Экспорт отчётов в CSV' },
            { id: 11, title: 'Дизайн попапов и модалок' },
        ],
    },
    {
        title: 'Готово',
        color: '#10b981',
        tasks: [
            { id: 12, title: 'Создать базовую структуру проекта' },
            { id: 13, title: 'Настроить CI/CD пайплайн' },
            { id: 14, title: 'Добавить базовые стили и тему' },
            { id: 15, title: 'Реализовать список спринтов' },
        ],
    },
];
</script>

<template>
    <Head title="Задачи" />

    <DashboardLayout fill-height>
        <div class="pt-4 min-h-0 flex-1 flex flex-col gap-4 overflow-hidden">
            <div class="flex items-center justify-between gap-3 shrink-0">
                <h1 class="title">Задачи</h1>
                <CreateTask :projects="projects" :tasks="tasks" />
            </div>

            <div class="grid grid-cols-4 gap-4 min-h-0 flex-1">
                <div
                    v-for="column in columns"
                    :key="column.title"
                    class="dashboard-inset min-h-0 min-w-0 flex flex-col gap-3 overflow-hidden"
                >
                    <div class="flex items-center gap-2 shrink-0">
                        <span
                            class="w-2 h-2 rounded-full shrink-0"
                            :style="{ backgroundColor: column.color }"
                        />
                        <span class="title-2 truncate">{{ column.title }}</span>
                        <span class="t-mini ml-auto">{{ column.tasks.length }}</span>
                    </div>

                    <div class="space-y-2 min-h-0 flex-1 overflow-y-auto no-scrollbar mt-4">
                        <div
                            v-for="task in column.tasks"
                            :key="task.id"
                            class="dashboard-inset"
                        >
                            <div class="title-3">{{ task.title }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
