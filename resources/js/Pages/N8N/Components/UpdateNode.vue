<script setup>
import ChoiceNodeType from './ChoiceNodeType.vue';
import Modal from '@/Components/Modal.vue';
import { nodeConfigFields } from './nodeConfigFields';

import ConditionBuilder from './Conditions/ConditionBuilder.vue';
import OutputBuilder from './OutputSchema/OutputBuilder.vue';

import { ref, watch } from 'vue';
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
});

const buildersMap = {
    ConditionBuilder,
    OutputBuilder,
};

const nodeId = () => props.nodeData?.id;
const config = ref({});

const nodeType = ref(props.nodeData?.type || '');
const title = ref(props.nodeData?.label || '');

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
                    <ChoiceNodeType @onSelectedNodeType="(type) => (nodeType = type)" />
                </div>

                <div>
                    <h3 class="title-2">
                        Конфиг
                        <span class="badge badge-pending">Обязательно</span>
                    </h3>

                    <div v-if="nodeType && nodeConfigFields[nodeType]">
                        <div v-for="field in nodeConfigFields[nodeType].fields || []" :key="field.name">
                            <h4 class="dashboard-row-title mt-3 text-sm">{{ field.label }}</h4>

                            <template v-if="field.type === 'text'">
                                <input v-model="config[field.name]" class="input mt-2 w-full" type="text" :placeholder="field.label" />
                            </template>

                            <template v-else-if="field.type === 'textarea'">
                                <textarea v-model="config[field.name]" class="input mt-2 w-full" :placeholder="field.label" />
                            </template>
                        </div>
                    </div>

                    <component
                        v-if="nodeType && nodeConfigFields[nodeType]?.builder"
                        :is="buildersMap[nodeConfigFields[nodeType].builder]"
                        v-model="config[nodeConfigFields[nodeType].builder_root]"
                        :nodes="nodes"
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
