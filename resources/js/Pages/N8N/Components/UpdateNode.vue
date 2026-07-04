<script setup>
import LibraryNodes from '../NodeTypeSelection/LibraryNodes.vue';
import Modal from '@/Components/Modal.vue';
import { nodeConfigFields } from './nodeConfigFields';
import { customFieldsMap } from './customFieldsMap';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import PasswordField from '@/Components/PasswordField.vue';
import RangeField from '@/Components/RangeField.vue';
import Rectangle from '@/Components/Skeleton/Rectangle.vue';
import ConfigQueriesConfigure from './ConfigQueriesConfigure.vue';

import ConditionBuilder from './Conditions/ConditionBuilder.vue';
import OutputBuilder from './OutputSchema/OutputBuilder.vue';

import { computed, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    nodeData: {
        type: Object,
        required: true,
    },
    nodes: {
        type: Array,
        required: true,
    },
    workflowId: {
        type: Number,
        default: null,
    },
});

console.log(props.nodeData);

const buildersMap = {
    ConditionBuilder,
    OutputBuilder,
    ConfigQueriesConfigure,
};

const nodeId = () => props.nodeData?.id;
const config = ref({});

const nodeType = ref(props.nodeData?.type || '');
const title = ref(props.nodeData?.label || '');

const backendOptions = ref({});
const backendLoading = ref({});

const resolvedWorkflowId = computed(() => {
    const fromProp = Number(props.workflowId);
    if (Number.isFinite(fromProp) && fromProp > 0) return fromProp;
    const fromNode = Number(props.nodeData?.workflow_id);
    return Number.isFinite(fromNode) && fromNode > 0 ? fromNode : null;
});

const emit = defineEmits(['close', 'onUpdatedNode', 'onDeletedNode']);

watch(
    () => props.nodeData,
    (newNode) => {
        if (!newNode) {
            return;
        }

        nodeType.value = newNode.type || '';
        title.value = newNode.label || '';

        const cfg = newNode.config
            ? typeof newNode.config === 'string'
                ? JSON.parse(newNode.config)
                : newNode.config
            : {};

        config.value = { ...cfg };
    },
    { immediate: true }
);

async function fetchBackendOptionsForField(field) {
    const req = field?.backend_request;
    if (!req) return;

    const routeConfig = req.route;
    let routeName = null;
    let params = [];

    if (typeof routeConfig === 'string') {
        routeName = routeConfig;
    } else if (routeConfig && typeof routeConfig === 'object') {
        routeName = routeConfig.name ?? null;
        if (routeConfig.zoomable) {
            params = resolvedWorkflowId.value ? [resolvedWorkflowId.value] : [];
        }
    }

    if (!routeName) return;

    backendLoading.value[field.name] = true;
    try {
        const res = await axios.get(route(routeName, ...params));
        const map = req.response_map;
        const items = map ? (res.data?.[map.root] || []) : [];

        backendOptions.value[field.name] = Array.isArray(items)
            ? items.map((item) => ({
                  label: item?.[map.label],
                  value: item?.[map.value],
              }))
            : [];
    } catch (e) {
        console.error(`Ошибка загрузки опций для поля ${field.name}`, e);
        backendOptions.value[field.name] = [];
    } finally {
        backendLoading.value[field.name] = false;
    }
}

watch(
    () => [nodeType.value, resolvedWorkflowId.value],
    async () => {
        const fields = nodeConfigFields[nodeType.value]?.fields ?? [];
        for (const field of fields) {
            if (!field.backend_request) continue;
            await fetchBackendOptionsForField(field);
        }
    },
    { immediate: true },
);

async function updateNode() {
    const id = nodeId();
    const updatedNode = {
        id,
        title: title.value,
        type: nodeType.value,
        config: config.value,
    };

    try {
        const response = await axios.put(route('update.node', id), updatedNode);

        if (response.data.result === 'ok') {
            emit('onUpdatedNode', response.data.node);
            emit('close');
        }
    } catch (error) {
        console.error('Ошибка при обновлении узла:', error);
    }
}

async function deleteNode() {
    const id = nodeId();
    try {
        const response = await axios.delete(route('delete.node', id));

        if (response.data.result === 'ok') {
            emit('onDeletedNode', id);
            emit('close');
        }
    } catch (e) {
        console.error('ОШИБКА ПРИ УДАЛЕНИИ НОДЫ: ', e);
    }
}

async function getQueries() {
    
}
</script>

<template>
    <Modal :show="show" max-width="4xl" @close="emit('close')">
        <div class="custom-scroll max-h-[90vh] space-y-4 overflow-y-auto p-4 md:p-6">
            <div class="flex flex-row items-center justify-between gap-3">
                <h2 class="title-2">
                    Параметры узла
                    <span class="label-content ml-2">{{ nodeData.label }}</span>
                </h2>
                <button type="button" class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit" aria-label="Закрыть" @click="emit('close')">
                    <i class="fa-solid fa-xmark text-xl" />
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4">

                <div>
                    <h3 class="title-2">
                        Название
                        <span class="badge badge-pending">Обязательно</span>
                    </h3>
                    <input
                        v-model="title"
                        class="input mt-2 w-full"
                        type="text"
                        placeholder="Название узла"
                    />
                </div>

                <div>
                    <h3 class="title-2">
                        Что делает
                        <span class="badge badge-pending">Обязательно</span>
                    </h3>
                    <div class="label-content mt-2 inline-flex">{{ nodeType }}</div>
                </div>

                <LibraryNodes @onSelectedNodeType="(type) => (nodeType = type)" />

                <div>
                    <h3 class="title-2">
                        Конфиг
                        <span class="badge badge-pending">Обязательно</span>
                    </h3>

                    <div v-if="nodeType && nodeConfigFields[nodeType]">
                        <div v-for="field in nodeConfigFields[nodeType].fields || []" :key="field.name">
                            <h4 class="dashboard-row-title mt-3 text-sm">{{ field.label }}</h4>

                            <template v-if="field.customField && customFieldsMap[field.customField]">
                                <component
                                    :is="customFieldsMap[field.customField]"
                                    v-model="config[field.name]"
                                    class="mt-2"
                                    :field="field"
                                    :nodes="nodes"
                                    :workflow-id="resolvedWorkflowId"
                                />
                            </template>

                            <template v-else-if="field.security">
                                <PasswordField
                                    v-model="config[field.name]"
                                    :placeholder="field.label"
                                />
                            </template>

                            <template v-else-if="field.backend_request">
                                <Rectangle
                                    v-if="backendLoading[field.name]"
                                    class="mt-2"
                                    height="2.5rem"
                                    rounded="rounded-xl"
                                />
                                <HeadlessSelect
                                    v-else
                                    v-model="config[field.name]"
                                    :options="backendOptions[field.name] || []"
                                    button-class="select-input mt-2 w-full"
                                    :placeholder="field.label"
                                />
                            </template>

                            <template v-else-if="field.type === 'text'">
                                <input v-model="config[field.name]" class="input mt-2 w-full" type="text" :placeholder="field.label" />
                            </template>

                            <template v-else-if="field.type === 'textarea'">
                                <textarea v-model="config[field.name]" class="input mt-2 w-full" :placeholder="field.label" />
                            </template>

                            <template v-else-if="field.type === 'number'">
                                <input
                                    v-model="config[field.name]"
                                    class="input mt-2 w-full"
                                    type="number"
                                    :placeholder="field.label"
                                    :step="field.step || '1'"
                                    :min="field.min"
                                    :max="field.max"
                                />
                            </template>

                            <template v-else-if="field.type === 'range'">
                                <RangeField
                                    v-model="config[field.name]"
                                    :min="field.min ?? 0"
                                    :max="field.max ?? 100"
                                    :step="field.step || '1'"
                                />
                            </template>

                            <template v-else-if="field.type === 'simple_select'">
                                <HeadlessSelect
                                    v-model="config[field.name]"
                                    :options="(field.options || []).map((option) => ({ label: option.name, value: option.value ?? option.name }))"
                                    button-class="select-input mt-2 w-full"
                                    :placeholder="field.label"
                                />
                            </template>
                        </div>
                    </div>

                    <component
                        v-if="nodeType && nodeConfigFields[nodeType]?.builder"
                        :is="buildersMap[nodeConfigFields[nodeType].builder]"
                        v-model="config[nodeConfigFields[nodeType].builder_root]"
                        :nodes="nodes"
                        :node-id="nodeId()"
                        :workflow-id="resolvedWorkflowId"
                        :on-save="updateNode"
                    />
                </div>

                <div class="flex flex-row flex-wrap items-center gap-3">
                    <button type="button" class="primary-btn flex items-center gap-2" @click="updateNode">
                        Сохранить изменения
                        <i class="fa-solid fa-floppy-disk" />
                    </button>

                    <button type="button" class="badge badge-pending cursor-pointer" @click="deleteNode">
                        Удалить узел
                        <i class="fa-solid fa-trash" />
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>
