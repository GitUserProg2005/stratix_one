<script setup>
import LibraryNodes from '../NodeTypeSelection/LibraryNodes.vue';
import ConditionBuilder from './Conditions/ConditionBuilder.vue';
import ConfigQueriesConfigure from './ConfigQueriesConfigure.vue';
import OutputBuilder from './OutputSchema/OutputBuilder.vue';
import Modal from '@/Components/Modal.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import PasswordField from '@/Components/PasswordField.vue';
import RangeField from '@/Components/RangeField.vue';
import SelectFieldsMode from './CustomFields/SelectFieldsMode.vue';

import { nodeConfigFields } from './nodeConfigFields';
import { customFieldsMap } from './customFieldsMap';
import { useNodeTypeWatcher } from '../utils/selectionWatch';

import { ref, watch } from 'vue';
import axios from 'axios';

const { workflowId, nodes } = defineProps({
    workflowId: Number,
    nodes: Array,
});

const buildersMap = {
    ConditionBuilder,
    OutputBuilder,
    ConfigQueriesConfigure,
};

const showModal = ref(false);
const nodeType = ref('');
const title = ref('');
const config = ref({});
const backendOptions = ref({});

useNodeTypeWatcher({
    nodeType,
    config,
    backendOptions,
    workflowId: () => workflowId,
    resetConfigOnChange: false,
});

const emit = defineEmits(['onCreatedNode']);

function isArrayBuilder(type) {
    return nodeConfigFields[type]?.builder === 'ConfigQueriesConfigure';
}

watch(() => nodeType.value, (type) => {
    const root = nodeConfigFields[type]?.builder_root;
    if (!root) {
        config.value = {};
        return;
    }
    // update_metric — массив; condition/output — объект (их билдеры сами задают дефолт)
    config.value = isArrayBuilder(type) ? { [root]: [] } : {};
});

function setBuilderValue(value) {
    const root = nodeConfigFields[nodeType.value]?.builder_root;
    if (!root) return;
    config.value = { ...config.value, [root]: value };
}

function openModal() {
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function createNode() {
    const position = { x: 100, y: 100 };
    const root = nodeConfigFields[nodeType.value]?.builder_root;
    const payloadConfig = { ...config.value };

    if (root && isArrayBuilder(nodeType.value) && !Array.isArray(payloadConfig[root])) {
        payloadConfig[root] = [];
    }

    const newNode = {
        workflow_id: workflowId,
        type: nodeType.value,
        order: nodes.length + 1,
        title: title.value,
        config: payloadConfig,
        position,
    };

    try {
        const response = await axios.post(route('create.node'), newNode);

        if (response.data.result === 'ok') {
            const created = response.data.node;
            emit('onCreatedNode', created);
            closeModal();
        }
    } catch (error) {
        console.error('Ошибка при создании узла:', error);
    }
}
</script>

<template>
    <div class="relative">
        <button type="button" class="primary-btn-white-blur flex items-center gap-2 text-sm" @click="openModal">
            Создать узел
            <i class="fa-solid fa-plus" />
        </button>

        <Modal :show="showModal" max-width="4xl" @close="closeModal">
            <div class="custom-scroll max-h-[90vh] space-y-4 overflow-y-auto p-4 md:p-6">
                <div class="flex flex-row items-center justify-between gap-3">
                    <h2 class="title-2">Параметры узла</h2>
                    <button type="button" class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit" aria-label="Закрыть" @click="closeModal">
                        <i class="fa-solid fa-xmark text-xl" />
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <h3 class="title-2">
                            Название
                            <span class="badge badge-pending">Обязательно</span>
                        </h3>
                        <input v-model="title" class="input mt-2 w-full" type="text" placeholder="Название узла" />
                    </div>

                    <div>
                        <LibraryNodes @onSelectedNodeType="(type) => (nodeType = type)" />
                    </div>

                    <div>
                        <h3 class="title-2">
                            Конфиг
                            <span class="badge badge-pending">Обязательно</span>
                        </h3>

                        <div v-if="nodeType && nodeConfigFields[nodeType]">
                            <section v-if="nodeConfigFields[nodeType].fields?.length">
                                <div v-for="field in nodeConfigFields[nodeType].fields || []" :key="field.name">
                                    <h4 class="dashboard-row-title mt-3 text-sm">
                                        {{ field.label }}
                                        <span v-if="field.required" class="badge badge-pending">Обязательно</span>
                                    </h4>

                                    <template v-if="field.customField && customFieldsMap[field.customField]">
                                        <component
                                            :is="customFieldsMap[field.customField]"
                                            v-model="config[field.name]"
                                            class="mt-2"
                                            :field="field"
                                            :nodes="nodes"
                                            :workflow-id="workflowId"
                                        />
                                    </template>

                                    <!-- Fields select mode -->
                                    <template v-else-if="field.type == 'fields_select'">
                                        <SelectFieldsMode
                                            v-model="config"
                                            :field="field"
                                        />
                                    </template>

                                    <template v-else-if="field.security">
                                        <PasswordField
                                            v-model="config[field.name]"
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

                                    <template v-else-if="field.type === 'backend_select'">
                                        <HeadlessSelect
                                            v-model="config[field.name]"
                                            :options="backendOptions[field.name] || []"
                                            button-class="select-input mt-2 w-full"
                                            :placeholder="field.label"
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
                            </section>

                            <div v-else-if="!nodeConfigFields[nodeType].builder" class="mt-2 ">
                                <h4 class="context">Не принимает параметров</h4>
                            </div>

                            <component
                                v-if="nodeConfigFields[nodeType].builder"
                                :is="buildersMap[nodeConfigFields[nodeType].builder]"
                                :model-value="config[nodeConfigFields[nodeType].builder_root]"
                                :nodes="nodes"
                                :node-id="0"
                                :workflow-id="workflowId"
                                @update:model-value="setBuilderValue"
                            />
                        </div>     
                    </div>

                    <div>
                        <button type="button" class="primary-btn flex items-center gap-2" @click="createNode">
                            Создать узел
                            <i class="fa-solid fa-plus" />
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
