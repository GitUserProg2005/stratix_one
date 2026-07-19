<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CreateProject from '@/Pages/Project/Create.vue';
import Avatar from '@/Components/Avatar.vue';
import BackButton from '@/Components/BackButton.vue';

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});

function projectOwner(project) {
    return project.users?.find((u) => u.is_owner) ?? null;
}

function projectMembers(project) {
    return (project.users ?? []).filter((u) => !u.is_owner);
}

const statusLabel = {
    started: 'Начатые',
    in_progress: 'В процессе',
    completed: 'Завершён',
};

const statusBadge = (status) => {
    if (status === 'completed') return 'badge badge-completed';
    if (status === 'in_progress') return 'badge badge-in-progress';
    return 'badge badge-pending';
};

const projectStatus = (project) => {
    return typeof project.status === 'object' ? project.status?.value : project.status;
};

const stats = computed(() => {
    const list = props.projects;
    const uniqueUserIds = new Set();
    let workflowsTotal = 0;
    let nodesTotal = 0;
    let started = 0;
    let inProgress = 0;
    let completed = 0;

    list.forEach((project) => {
        const status = projectStatus(project);
        if (status === 'started') started += 1;
        if (status === 'in_progress') inProgress += 1;
        if (status === 'completed') completed += 1;

        (project.users ?? []).forEach((user) => uniqueUserIds.add(user.id));

        (project.workflows ?? []).forEach((workflow) => {
            workflowsTotal += 1;
            nodesTotal += Number(workflow.nodes_count ?? 0);
        });
    });

    return {
        total: list.length,
        started,
        inProgress,
        completed,
        members: uniqueUserIds.size,
        workflows: workflowsTotal,
        nodes: nodesTotal,
    };
});

const visibleMembers = (project, limit = 4) => projectMembers(project).slice(0, limit);
const hiddenMembersCount = (project, limit = 4) => Math.max(0, projectMembers(project).length - limit);
</script>

<template>
    <Head title="Проекты" />

    <DashboardLayout>
        <div class="pt-4 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-4">
                    <BackButton :href="route('dashboard')" />
                    <h1 class="title">Проекты</h1>
                </div>

                <CreateProject />
            </div>

            <div class="grid grid-cols-1 gap-16 lg:grid-cols-[1fr_2fr]">
                <!-- Левая колонка: статистика -->
                <aside class="lg:sticky lg:top-4 lg:self-start grid grid-cols-2 gap-4">
                    <div class="dashboard-inset">
                        <p>Всего проектов</p>
                        <div class="title-font-2 mt-1 text-[#fc7d61]">{{ stats.total }}</div>
                    </div>

                    <div class="dashboard-inset">
                        <p>Начатые</p>
                        <div class="title-font-2 mt-1 text-[#fc7d61]">{{ stats.started }}</div>
                    </div>

                    <div class="dashboard-inset">
                        <p>В процессе</p>
                        <div class="title-font-2 mt-1 text-[#fc7d61]">{{ stats.inProgress }}</div>
                    </div>

                    <div class="dashboard-inset">
                        <p>Завершённые</p>
                        <div class="title-font-2 mt-1 text-[#fc7d61]">{{ stats.completed }}</div>
                    </div>

                    <div class="dashboard-inset">
                        <p>Участники</p>
                        <div class="title-font-2 mt-1 text-[#fc7d61]">{{ stats.members }}</div>
                    </div>

                    <div class="dashboard-inset">
                        <p>Автоматизации</p>
                        <div class="title-font-2 mt-1 text-[#fc7d61]">{{ stats.workflows }}</div>
                    </div>
                </aside>

                <!-- Правая колонка: карточки -->
                <section class="space-y-3 min-w-0">
                    <div v-if="!projects.length" class="dashboard-inset">
                        <p class="context">Проектов пока нет — создайте первый</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="project in projects"
                            :key="project.id"
                            class="dashboard-inset space-y-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="title-2 truncate">{{ project.title }}</h2>
                                <span :class="statusBadge(projectStatus(project))">
                                    {{ statusLabel[projectStatus(project)] ?? projectStatus(project) }}
                                </span>
                            </div>

                            <div v-if="projectOwner(project)" class="my-2 flex items-center gap-2">
                                <Avatar
                                    :name="projectOwner(project).name"
                                    :src="projectOwner(project).avatar_url"
                                    :user-id="projectOwner(project).id"
                                    :no-link="true"
                                    size="sm"
                                />
                                <div class="flex flex-col">
                                    <span class="title-3">{{ projectOwner(project).name }}</span>
                                    <span class="badge-neutral mt-2">Создатель</span>
                                </div>
                            </div>

                            <div>
                                <h3 class="title-3 mb-2"> 
                                    <i class="fa-solid fa-user-group mr-2"></i>
                                    Участники
                                </h3>
                                <div v-if="projectMembers(project).length" class="flex items-center gap-2 flex-wrap">
                                    <div
                                        v-for="user in visibleMembers(project)"
                                        :key="user.id"
                                        class="flex items-center gap-2"
                                        :title="user.name"
                                    >
                                        <Avatar
                                            :name="user.name"
                                            :src="user.avatar_url"
                                            :user-id="user.id"
                                            :no-link="true"
                                            size="sm"
                                        />
                                    </div>
                                    <span v-if="hiddenMembersCount(project)" class="t-mini">
                                        +{{ hiddenMembersCount(project) }}
                                    </span>
                                </div>
                                <p v-else class="context">Нет участников</p>
                            </div>

                            <div>
                                <h3 class="title-3 mb-2">
                                    <i class="fa-solid fa-robot mr-2"></i>
                                    Автоматизации
                                </h3>
                                <div v-if="project.workflows?.length" class="flex flex-wrap items-center gap-2">
                                    <div
                                        v-for="workflow in project.workflows"
                                        :key="workflow.id"
                                        class="badge-neutral inline-flex h-8 items-center gap-2"
                                    >
                                        <span class="truncate leading-none">{{ workflow.name }}</span>
                                        <span class="w-1 h-1 rounded-full bg-white shrink-0" />
                                        <span class="shrink-0 leading-none">{{ workflow.nodes_count }} узлов</span>
                                    </div>
                                </div>
                                <p v-else class="context">Нет автоматизаций</p>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </DashboardLayout>
</template>
