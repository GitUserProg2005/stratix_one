<script setup>
import Modal from '@/Components/Modal.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import axios from 'axios';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const showModal = ref(false);

const workflows = ref([]);
const isWorkflowsLoading = ref(false);
const selectedWorkflowId = ref('');

const title = ref('');

const workflowOptions = computed(() => {
    const options = [{ value: '', label: 'Не выбрано' }];
    for (const workflow of workflows.value || []) {
        if (!workflow) continue;
        options.push({
            value: String(workflow.id),
            label: String(workflow.name ?? `Workflow ${workflow.id}`),
        });
    }
    return options;
});

const canSubmit = computed(() => Boolean(title.value.trim()));

async function loadWorkflows() {
    if (isWorkflowsLoading.value) return;
    if (workflows.value.length) return;

    isWorkflowsLoading.value = true;
    try {
        const { data } = await axios.get(route('get.workflows'));
        workflows.value = Array.isArray(data?.workflows) ? data.workflows : [];
    } catch (e) {
        console.error('Не удалось загрузить workflows', e);
        workflows.value = [];
    } finally {
        isWorkflowsLoading.value = false;
    }
}

function openModal() {
    showModal.value = true;
    loadWorkflows();
}

function closeModal() {
    showModal.value = false;
}

async function createDashboard() {
    if (!canSubmit.value) return;

    const workflowId = selectedWorkflowId.value === '' ? null : Number(selectedWorkflowId.value);

    try {
        await axios.post(route('dashboard.create'), {
            title: title.value,
            workflow_id: Number.isFinite(workflowId) ? workflowId : null,
        });

        title.value = '';
        selectedWorkflowId.value = '';
        closeModal();

        router.reload({ only: ['dashboards'] });
    } catch (e) {
        console.error(e);
    }
}
</script>

<template>
    <button class="primary-btn" @click="openModal">
        Создать дашборд +
    </button>

    <Modal :show="showModal" @close="closeModal">
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="title-2">Создать дашборд</h2>
                <button class="btn" @click="closeModal">
                    <i class="fa-solid fa-xmark" />
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2 md:col-span-1">
                    <label class="context text-sm">Workflow</label>
                    <HeadlessSelect
                        v-model="selectedWorkflowId"
                        :options="workflowOptions"
                        button-class="select-input mt-2 w-full"
                        :placeholder="isWorkflowsLoading ? 'Загрузка...' : 'Не выбрано'"
                    />
                </div>

                <div class="space-y-2 md:col-span-1">
                    <label class="context text-sm">Название</label>
                    <input
                        v-model="title"
                        type="text"
                        class="input w-full"
                        placeholder="Например, Продажи"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="closeModal">
                    Отмена
                </button>
                <button type="button" class="primary-btn" :disabled="!canSubmit" @click="createDashboard">
                    Создать
                </button>
            </div>
        </div>
    </Modal>
</template>

