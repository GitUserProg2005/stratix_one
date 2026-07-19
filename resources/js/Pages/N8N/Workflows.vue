<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import Rectangle from '@/Components/Skeleton/Rectangle.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
import axios from 'axios';

const workflows = ref([]);
const projects = ref([]);
const projectId = ref(null);
const isLoading = ref(false);
const nameNewWorkflow = ref('');
const editingWorkflow = ref(null);
const editedName = ref('');

const projectOptions = computed(() => [
    { value: null, label: 'Без проекта' },
    ...projects.value.map((p) => ({ value: p.id, label: p.title })),
]);

async function getWorkflows() {
    isLoading.value = true;
    try {
        const response = await axios.get(route('get.workflows'));
        if (response.data.result === 'ok') {
            workflows.value = response.data.workflows;
        }
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
    }
}

async function getProjects() {
    try {
        const response = await axios.get(route('projects.list'));
        if (response.data.result === 'ok') {
            projects.value = response.data.projects;
        }
    } catch (e) {
        console.error(e);
    }
}

async function createWorkflow() {
    if (!nameNewWorkflow.value.trim()) {
        return;
    }
    isLoading.value = true;
    try {
        const response = await axios.post(route('create.workflow'), {
            name: nameNewWorkflow.value.trim(),
            meta: null,
            project_id: projectId.value,
        });
        if (response.data.result === 'ok') {
            workflows.value.push(response.data.workflow);
            nameNewWorkflow.value = '';
            projectId.value = null;
        }
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
    }
}

async function deleteWorkflow(id) {
    if (!confirm('Удалить workflow?')) {
        return;
    }
    try {
        const response = await axios.delete(route('delete.workflow', id));
        if (response.data.result === 'ok') {
            workflows.value = workflows.value.filter((w) => w.id !== id);
        }
    } catch (e) {
        console.error(e);
    }
}

function startEditing(workflow) {
    editingWorkflow.value = workflow.id;
    editedName.value = workflow.name;
    nextTick(() => {
        const inp = document.querySelector(`#edit-workflow-input-${workflow.id}`);
        if (inp) {
            inp.focus();
        }
    });
}

async function saveEditing(workflow) {
    if (!editedName.value.trim()) {
        return;
    }
    isLoading.value = true;
    try {
        const response = await axios.post(route('update.workflow', workflow.id), {
            name: editedName.value,
        });
        if (response.data.result === 'ok') {
            workflow.name = editedName.value;
        }
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
        editingWorkflow.value = null;
    }
}

function handleEditKeydown(event, workflow) {
    if (event.key === 'Enter') {
        saveEditing(workflow);
    } else if (event.key === 'Escape') {
        editingWorkflow.value = null;
    }
}

getProjects();
getWorkflows();
</script>

<template>
    <Head title="Workflows" />
    <AppLayout>
        <div class="mx-auto max-w-3xl p-4 lg:p-6">

            <h1 class="title mb-4">Workflows</h1>
            <p class="context mb-6">
                Визуальные сценарии: ноды, связи, запуск и лог.
            </p>

            <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center">
                <input
                    v-model="nameNewWorkflow"
                    type="text"
                    class="input-underline flex-1"
                    placeholder="Название нового workflow"
                    @keydown.enter="createWorkflow"
                />
                <HeadlessSelect
                    v-model="projectId"
                    class="sm:w-52 shrink-0"
                    :options="projectOptions"
                    placeholder="Проект"
                />
                <button type="button" class="primary-btn shrink-0 text-sm" :disabled="isLoading" @click="createWorkflow">
                    Создать
                </button>
            </div>

            <div
                v-if="isLoading && !workflows.length"
                class="content-outline overflow-hidden p-0"
                aria-busy="true"
                aria-label="Загрузка workflows"
            >
                <template v-for="i in 4" :key="i">
                    <hr v-if="i > 1" class="workflow-list-divider">
                    <div class="px-3 py-2.5">
                        <Rectangle height="1rem" width="45%" rounded="rounded-sm" />
                    </div>
                </template>
            </div>

            <div v-else-if="workflows.length" class=" max-h-[min(24rem,50vh)] overflow-y-auto no-scrollbar p-0">
                <template v-for="(workflow, index) in workflows" :key="workflow.id">
                    <hr v-if="index > 0" class="workflow-list-divider">

                    <div v-if="editingWorkflow === workflow.id" class="px-3 py-2">
                        <input
                            :id="`edit-workflow-input-${workflow.id}`"
                            v-model="editedName"
                            class="input w-full text-sm"
                            @keydown="(e) => handleEditKeydown(e, workflow)"
                            @blur="() => saveEditing(workflow)"
                        />
                    </div>

                    <ContextMenu v-else>
                        <div class="sidebar-nav-link group rounded-none px-3 py-2">
                            <Link
                                :href="route('show.workflow', workflow.id)"
                                class="min-w-0 flex-1 truncate text-sm"
                            >
                                <span class="dashboard-row-title truncate font-normal">{{ workflow.name }}</span>
                            </Link>

                            <button
                                type="button"
                                class="badge badge-pending shrink-0 opacity-0 transition-opacity group-hover:opacity-100"
                                @click.stop="deleteWorkflow(workflow.id)"
                            >
                                Удалить
                            </button>
                        </div>

                        <template #menu="{ toggleOpen }">
                            <div class="flex flex-col gap-4">
                                <button
                                    type="button"
                                    class="w-full whitespace-nowrap text-left text-sm"
                                    @click="startEditing(workflow); toggleOpen()"
                                >
                                    Переименовать workflow
                                </button>
                            </div>
                        </template>
                    </ContextMenu>
                </template>
            </div>

            <p v-if="!isLoading && !workflows.length" class="context mt-4">
                Пока нет workflow — создайте первый.
            </p>
        </div>
    </AppLayout>
</template>
