<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Workflow from './Workflow.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import axios from 'axios';

const workflows = ref([]);
const isLoading = ref(false);
const nameNewWorkflow = ref('');
const editingWorkflow = ref(null);
const editedName = ref('');

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

async function createWorkflow() {
    if (!nameNewWorkflow.value.trim()) {
        return;
    }
    isLoading.value = true;
    try {
        const response = await axios.post(route('create.workflow'), {
            name: nameNewWorkflow.value.trim(),
            meta: null,
        });
        if (response.data.result === 'ok') {
            workflows.value.push(response.data.workflow);
            nameNewWorkflow.value = '';
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

getWorkflows();
</script>

<template>
    <Head title="Workflows" />
    <AppLayout>
        <div class="max-w-4xl p-4 lg:p-6">
            <h1 class="title mb-4">Workflows</h1>
            <p class="context mb-6">
                Визуальные сценарии: ноды, связи, запуск и лог.
            </p>

            <div class="mb-6 flex flex-col gap-2 sm:flex-row">
                <input
                    v-model="nameNewWorkflow"
                    type="text"
                    class="input flex-1 text-sm"
                    placeholder="Название нового workflow"
                    @keydown.enter="createWorkflow"
                />
                <button type="button" class="primary-btn shrink-0 text-sm" :disabled="isLoading" @click="createWorkflow">
                    Создать
                </button>
            </div>

            <div v-if="isLoading && !workflows.length" class="context">Загрузка…</div>

            <ul v-else class="space-y-3">
                <li v-for="workflow in workflows" :key="workflow.id" class="content">
                    <template v-if="editingWorkflow === workflow.id">
                        <input
                            :id="`edit-workflow-input-${workflow.id}`"
                            v-model="editedName"
                            class="input mb-2 w-full text-sm"
                            @keydown="(e) => handleEditKeydown(e, workflow)"
                            @blur="() => saveEditing(workflow)"
                        />
                    </template>
                    <template v-else>
                        <div class="flex items-center gap-2">
                            <ContextMenu>
                                <!--<Workflow :workflow="workflow" @delete="deleteWorkflow" />-->
                                <Link :href="route('show.workflow', workflow.id)" 
                                >
                                    <div class="flex flex-row justify-between items-center gap-2 py-2">
                                        <button
                                            type="button"
                                            class="sidebar-nav-link flex-1 min-w-0 truncate justify-start text-left"
                                            @click="toggleModal"
                                        >
                                            <span class="dashboard-row-title truncate">{{ workflow.name }}</span>
                                        </button>
                                        <button type="button" class="badge badge-pending shrink-0" @click.stop="deleteWorkflow(workflow.id)">
                                            Удалить
                                        </button>
                                    </div>
                                </Link>
                                
                                <template #menu="{ toggleOpen }">
                                    <div class="flex flex-col gap-4">
                                        <button type="button" 
                                            class="w-full text-left text-sm whitespace-nowrap" 
                                            @click="startEditing(workflow); toggleOpen()">
                                            Переименовать workflow
                                        </button>
                                    </div>
                                </template>
                            </ContextMenu>
                        </div>
                    </template>
                </li>
            </ul>

            <p v-if="!isLoading && !workflows.length" class="context mt-4">
                Пока нет workflow — создайте первый.
            </p>
        </div>
    </AppLayout>
</template>
