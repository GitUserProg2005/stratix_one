<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CreateProject from '@/Pages/Project/Create.vue';
import UpdateProject from '@/Pages/Project/Update.vue';
import Avatar from '@/Components/Avatar.vue';
import BackButton from '@/Components/BackButton.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import Filter from '@/Pages/Project/Filter.vue';

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});

const updateRef = ref(null);

const filteredProjects = ref(props.projects);

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
        <div class="p-2 lg:p-4 space-y-4">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-start lg:justify-between gap-3">
                <div class="flex items-center gap-4">
                    <BackButton :href="route('dashboard')" />
                    <h1 class="title">Проекты</h1>
                </div>

                <div class="flex items-center gap-4">
                    <Filter :projects="projects" @update:filteredProjects="filteredProjects = $event" />
                    <CreateProject />
                </div>
            </div>

            <UpdateProject ref="updateRef" />

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_2fr] mt-8">
                <aside class="lg:sticky lg:top-4 lg:self-start grid grid-cols-1 lg:grid-cols-2 gap-4">
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

                <section class="space-y-3 min-w-0">
                    <div v-if="!projects.length" class="dashboard-inset">
                        <p class="context">Проектов пока нет — создайте первый</p>
                    </div>

                    <div v-else-if="!filteredProjects.length" class="dashboard-inset">
                        <p class="context">Нет проектов с таким статусом</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <ContextMenu
                            v-for="project in filteredProjects"
                            :key="project.id"
                        >
                            <article class="flex h-full flex-col dashboard-inset space-y-6">
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
                                        <i class="fa-solid fa-user-group mr-2" />
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

                                <div class="mt-auto">
                                    <h3 class="title-3 mb-2">
                                        <i class="fa-solid fa-robot mr-2" />
                                        Автоматизации
                                    </h3>
                                    <div v-if="project.workflows?.length" class="flex flex-wrap items-center gap-2">
                                        <Link
                                            v-for="workflow in project.workflows"
                                            :key="workflow.id"
                                            :href="route('show.workflow', workflow.id)"
                                            class="badge-neutral inline-flex h-8 items-center gap-2"
                                        >
                                            <span class="truncate leading-none">{{ workflow.name }}</span>
                                            <span class="w-1 h-1 rounded-full bg-white shrink-0" />
                                            <span class="shrink-0 leading-none">{{ workflow.nodes_count }} узлов</span>
                                        </Link>
                                    </div>
                                    <p v-else class="context">Нет автоматизаций</p>
                                </div>
                            </article>

                            <template #menu="{ toggleOpen }">
                                <button
                                    type="button"
                                    class="w-full whitespace-nowrap text-left text-sm"
                                    @click="updateRef?.openModal(project, projectMembers(project)); toggleOpen($event)"
                                >
                                    Редактировать проект
                                </button>
                            </template>
                        </ContextMenu>
                    </div>
                </section>
            </div>
        </div>
    </DashboardLayout>
</template>
