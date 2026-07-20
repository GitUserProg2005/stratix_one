<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { VueDraggable } from 'vue-draggable-plus';
import axios from 'axios';

import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CreateTask from '@/Pages/Task/Create.vue';
import UpdateTask from '@/Pages/Task/Update.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import Members from '@/Components/Members.vue';
import TaskCard from '@/Pages/Task/Card.vue';

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

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id ?? null);

function flattenTasks(list, result = []) {
    for (const task of list) {
        result.push(task);

        if (task.children?.length) {
            flattenTasks(task.children, result);
        }
    }

    return result;
}

const tasks = ref([...props.tasks]);
const tasksFlat = computed(() => flattenTasks(tasks.value));
const selectedProject = ref(null);
const updateRef = ref(null);

const projectOptions = computed(() => [
    { value: null, label: 'Все проекты' },
    ...props.projects.map((p) => ({ value: p.id, label: p.title })),
]);

const selectedProjectMembers = computed(() => {
    if (!selectedProject.value) {
        return [];
    }

    const project = props.projects.find(
        (p) => Number(p.id) === Number(selectedProject.value),
    );

    return (project?.memberships ?? [])
        .map((membership) => membership.user)
        .filter(Boolean);
});

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
        color: '#f59e0b',
        tasks: [],
    },
    {
        title: 'На проверке',
        status: 'review',
        color: '#3b82f6',
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

function findTaskInTree(list, id) {
    for (const task of list) {
        if (Number(task.id) === Number(id)) {
            return task;
        }

        if (task.children?.length) {
            const found = findTaskInTree(task.children, id);

            if (found) {
                return found;
            }
        }
    }

    return null;
}

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

// После DnD синхронизируем status задачи со статусом колонки
function syncTaskStatusToColumn(column) {
    column.tasks.forEach((task) => {
        if (taskStatus(task) === column.status) return;

        task.status = column.status;

        const master = findTaskInTree(tasks.value, task.id);
        if (master) {
            master.status = column.status;
        }

        updateTaskStatus(task, column.status);
    });
}

async function updateTaskStatus(task, status) {
    try {
        await axios.post(route('tasks.update-status', task.id), { status });
    } catch (error) {
        toast.error('Не удалось обновить статус задачи');
    }
}

function openEditModal(task) {
    updateRef.value?.openModal(task);
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

                <CreateTask :projects="projects" :tasks="tasksFlat" />
                <UpdateTask ref="updateRef" :projects="projects" :tasks="tasksFlat" />
            </div>

            <!--1fr - левый column; 2fr - основной контент-->
            <div class="grid min-h-0 flex-1 grid-cols-1 items-start gap-4 lg:grid-cols-[16rem_2fr]">
                <!--Участники проекта-->
                <Members :members="selectedProjectMembers" class="h-fit w-full" />

                <!--Колонки задач-->
                <div class="h-full grid min-h-0 grid-cols-4 gap-4">
                    <div
                        v-for="column in columns"
                        :key="column.status"
                        class="dashboard-inset min-h-0 min-w-0 flex flex-col gap-4 overflow-hidden"
                    >
                        <div class="flex items-center gap-4 shrink-0">
                            <span
                                class="w-2.5 h-3 [clip-path:polygon(50%_0%,_100%_25%,_100%_75%,_50%_100%,_0%_75%,_0%_25%)] shrink-0"
                                :style="{ backgroundColor: column.color }"
                            />

                            <span class="title-2 truncate">{{ column.title }}</span>
                            <span>{{ column.tasks.length }}</span>
                        </div>

                        <!-- Один VueDraggable = одна колонка (список). group общий → DnD между колонками -->
                        <VueDraggable
                            v-model="column.tasks"
                            group="tasks"
                            :animation="150"
                            class="mt-4 min-h-[6rem] flex-1 space-y-4 overflow-y-auto no-scrollbar"
                            @add="syncTaskStatusToColumn(column)"
                        >
                            <TaskCard
                                v-for="task in column.tasks"
                                :key="task.id"
                                :task="task"
                                :current-user-id="currentUserId"
                                @edit="openEditModal"
                            />
                        </VueDraggable>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
