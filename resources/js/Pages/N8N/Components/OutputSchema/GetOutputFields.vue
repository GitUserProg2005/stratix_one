<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    nodes: {
        type: Array,
        required: true,
    },
    modelValue: Object,
});

const emit = defineEmits(['update:modelValue']);
const selectedNodeId = ref('');
const availableFields = ref([]);
const selectedField = ref(props.modelValue ?? '');

function extractFields(output, basePath = '') {
    if (!output) return [];

    if (Array.isArray(output)) {
        return output.flatMap(item => extractFields(item, basePath));
    }

    if (output.type === 'field') {
        return [{
            key: output.key,
            path: basePath ? `${basePath}.${output.key}` : output.key,
        }];
    }

    if (output.type === 'group' && Array.isArray(output.fields)) {
        const nextPath = output.name ? 
            (basePath ? `${basePath}.${output.name}` : output.name) 
            : basePath;
        return output.fields.flatMap(f => extractFields(f, nextPath));
    }

    return [];
}

function parseConfig(configStr) {
    if (!configStr) return null;

    if (typeof configStr === 'string') {
        try {
            return JSON.parse(configStr);
        } catch (e) {
            console.error('Ошибка парсинга config:', e);
            return null;
        }
    }

    if (typeof configStr === 'object') {
        return configStr;
    }

    return null;
}

watch(() => props.nodes, () => {
    if (!props.modelValue) return;

    const { node_id, path } = props.modelValue || {};

    selectedNodeId.value = node_id || '';

    const node = props.nodes.find(n => n.id === node_id);

    if (!node?.data?.config) return;

    const parsedConfig = parseConfig(node.data.config);
    if (!parsedConfig?.output) return;

    availableFields.value = extractFields(parsedConfig.output);
    selectedField.value = path || '';
}, { immediate: true });

watch(selectedNodeId, (id, prevId) => {
    if (id === prevId) return;

    const node = props.nodes.find(n => n.id === id);

    if (!node?.data?.config) {
        availableFields.value = [];
        selectedField.value = '';
        return;
    }

    const parsedConfig = parseConfig(node.data.config);
    if (!parsedConfig?.output) return;

    availableFields.value = extractFields(parsedConfig.output);

    if (props.modelValue?.node_id !== id) {
        selectedField.value = '';
    }
});


watch(selectedField, (path) => {
    if (!path) {
        emit('update:modelValue', '');
        return;
    }

    try {
        emit('update:modelValue', {
            node_id: selectedNodeId.value,
            path: path
        });
    } catch (e) {
        console.error('Ошибка парсинга выбранного поля:', e);
    }
});
</script>

<template>
    <div v-if="nodes.length" class="t-small">
        <select v-model="selectedNodeId" class="select-input mt-3 w-full min-w-[8rem]">
            <option value="">Выберите ноду</option>
            <option 
                v-for="node in nodes"
                :key="node.id"
                :value="node.id"
            >
                {{ node.data.label }}
            </option>
        </select>

        <div 
            v-if="availableFields.length" 
            class="mt-3"
        >
            <select v-model="selectedField" class="select-input w-full min-w-[8rem]">
                <option value="">Выберите поле</option>
                <option 
                    v-for="field in availableFields"
                    :key="field.key"
                    :value="field.path"
                    >
                        {{ field.path }}
                    </option>
            </select>
        </div>
    </div>
</template>