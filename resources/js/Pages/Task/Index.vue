<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CreateTask from '@/Pages/Task/Create.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
    projects: {
        type: Array,
        default: () => [],
    },
});

const tasks = ref([...props.tasks]);
const selectedProject = ref(null);

const projectOptions = computed(() => [
    { value: null, label: 'Все проекты' },
    ...props.projects.map((p) => ({ value: p.id, label: p.title })),
]);

const columns = ref([
    {
        title: 'К выполнению',
        status: 'started',
        color: '#ef4444',
        tasks: [],
    },
    {
        title: 'В работе',
        status: 'in_progress',
        color: '#3b82f6',
        tasks: [],
    },
    {
        title: 'На проверке',
        status: 'review',
        color: '#f59e0b',
        tasks: [],
    },
    {
        title: 'Готово',
        status: 'completed',
        color: '#10b981',
        tasks: [],
    },
]);

function taskStatus(task) {
    return typeof task.status === 'object' ? task.status?.value : task.status;
}

function taskDifficulty(task) {
    return typeof task.difficulty === 'object' ? task.difficulty?.value : task.difficulty;
}

function taskDifficultyColor(task) {
    switch (taskDifficulty(task)) {
        case 'easy':
            return '#10b981';
        case 'normal':
            return '#f59e0b';
        case 'hard':
            return '#ef4444';
        default:
            return '#6b7280';
    }
}

const difficultyLabel = {
    easy: 'Легкая',
    normal: 'Средняя',
    hard: 'Сложная',
};

function filterTasks() {
    if (selectedProject.value) {
        return tasks.value.filter(
            (task) => Number(task.project_id) === Number(selectedProject.value),
        );
    }

    return tasks.value;
}

function correlateTasks() {
    const list = filterTasks();

    columns.value.forEach((column) => {
        column.tasks = list.filter((task) => taskStatus(task) === column.status);
    });
}

watch(selectedProject, () => {
    correlateTasks();
});

watch(
    () => props.tasks,
    (value) => {
        tasks.value = [...value];
        correlateTasks();
    },
);

onMounted(() => {
    correlateTasks();
});
</script>

<template>
    <Head title="Задачи" />

    <DashboardLayout fill-height>
        <div class="pt-4 min-h-0 flex-1 flex flex-col gap-4 overflow-hidden">
            <div class="flex items-center justify-between gap-3 shrink-0">
                <h1 class="title">Задачи</h1>
            </div>

            <div class="flex items-center justify-between gap-3">
                <div class="my-4 shrink-0 sm:max-w-xs">
                    <HeadlessSelect
                        v-model="selectedProject"
                        :options="projectOptions"
                        placeholder="Проект"
                    />
                </div>

                <CreateTask :projects="projects" :tasks="tasks" />
            </div>

            <div class="grid grid-cols-4 gap-4 min-h-0 flex-1">
                <div
                    v-for="column in columns"
                    :key="column.title"
                    class="dashboard-inset min-h-0 min-w-0 flex flex-col gap-4 overflow-hidden"
                >
                    <div class="flex items-center gap-4 shrink-0">
                        <span
                            class="w-2.5 h-3 [clip-path:polygon(50%_0%,_100%_25%,_100%_75%,_50%_100%,_0%_75%,_0%_25%)] shrink-0"
                            :style="{ backgroundColor: column.color }"
                        />
                        <span class="title-2 truncate">{{ column.title }}</span>
                        <span class="">{{ column.tasks.length }}</span>
                    </div>

                    <div class="space-y-4 min-h-0 flex-1 overflow-y-auto no-scrollbar mt-4">
                        <div
                            v-for="task in column.tasks"
                            :key="task.id"
                            class="dashboard-inset space-y-2"
                        >
                            <div class="opacity-70">
                                <span class="context">
                                    <i class="fa-brands fa-diaspora text-sm mr-2"></i>
                                    {{ task.project.title }}
                                </span>
                            </div>

                            <div class="title-3 mt-4">{{ task.title }}</div>

                            <div class="flex items-center gap-2 context pt-4">
                                <span>Сложность: </span>

                                <span
                                    class="w-2 h-2 rounded-full shrink-0"
                                    :style="{ backgroundColor: taskDifficultyColor(task) }"
                                />
                                
                                <span>
                                    {{ difficultyLabel[taskDifficulty(task)] ?? taskDifficulty(task) }}
                                </span>
                            </div>

                            <div v-if="task.workers?.length" class="flex flex-wrap items-center gap-2">
                                <div
                                    v-for="worker in task.workers"
                                    :key="worker.id"
                                    :title="worker.name"
                                >
                                    <div class="badge-neutral flex items-center gap-2">
                                        <Avatar
                                            :name="worker.name"
                                            :src="worker.avatar_url"
                                            :user-id="worker.id"
                                            :no-link="true"
                                            size="sm"
                                        />
                                        
                                        <div class="w-1 h-1 bg-white rounded-full shrink-0"></div>

                                        <span class="context">{{ worker.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
