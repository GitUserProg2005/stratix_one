<script setup>
import { computed, ref, watch } from 'vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import { useNodeSchemas } from '../../composables/useNodeSchemas';
import { resolveDynamicSchema } from '../../utils/resolveDynamicSchema';

const props = defineProps({
    nodes: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: [Object, String],
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const { schemas } = useNodeSchemas();

const selectedNodeId = ref('');
const availableFields = ref([]);
const selectedField = ref('');

const nodeOptions = computed(() => {
    const options = [{ value: '', label: 'Выберите ноду' }];
    for (const node of props.nodes || []) {
        options.push({
            value: String(node.id),
            label: node.data?.label ?? String(node.id),
        });
    }
    return options;
});

const fieldOptions = computed(() => {
    const options = [{ value: '', label: 'Выберите поле' }];
    for (const field of availableFields.value) {
        options.push({ value: field.path, label: field.path });
    }
    return options;
});

function extractFields(output, basePath = '') {
    if (!output) return [];

    if (Array.isArray(output)) {
        return output.flatMap((item) => extractFields(item, basePath));
    }

    if (output.type === 'field') {
        return [{
            key: output.key,
            path: basePath ? `${basePath}.${output.key}` : output.key,
        }];
    }

    if (output.type === 'group' && Array.isArray(output.fields)) {
        const nextPath = output.name
            ? (basePath ? `${basePath}.${output.name}` : output.name)
            : basePath;
        return output.fields.flatMap((f) => extractFields(f, nextPath));
    }

    if (output.type === 'array' && output.items) {
        const nextPath = output.name
            ? (basePath ? `${basePath}.${output.name}` : output.name)
            : basePath;
        return extractFields(output.items, nextPath);
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

// Схема: dynamic из config → static outputSchema из registry
function resolveOutputSchema(node) {
    if (!node?.data) return null;

    const type = node.data.type;
    const nodeSchema = schemas.value?.[type] || null;
    const config = parseConfig(node.data.config) || {};

    const dynamic = resolveDynamicSchema(node, 'output', nodeSchema);
    if (dynamic) return dynamic;

    for (const key of ['output', 'request', 'payload']) {
        if (config[key] && typeof config[key] === 'object') {
            return config[key];
        }
    }

    return nodeSchema?.outputSchema || null;
}

function loadFieldsForNode(nodeId) {
    const id = String(nodeId || '');
    const node = (props.nodes || []).find((n) => String(n.id) === id);

    if (!node) {
        availableFields.value = [];
        return;
    }

    availableFields.value = extractFields(resolveOutputSchema(node));
}

function syncFromModel() {
    const value = props.modelValue;
    if (!value || typeof value !== 'object') {
        selectedNodeId.value = '';
        selectedField.value = '';
        availableFields.value = [];
        return;
    }

    selectedNodeId.value = value.node_id != null ? String(value.node_id) : '';
    loadFieldsForNode(selectedNodeId.value);
    selectedField.value = value.path || '';
}

watch(
    () => [props.modelValue, props.nodes, schemas.value],
    () => {
        syncFromModel();
    },
    { immediate: true, deep: true },
);

function onNodeSelect(id) {
    selectedNodeId.value = id ? String(id) : '';
    selectedField.value = '';
    loadFieldsForNode(selectedNodeId.value);

    // Не шлём "" — Laravel ConvertEmptyStringsToNull превратит left в null
    emit('update:modelValue', null);
}

function onFieldSelect(path) {
    selectedField.value = path || '';

    if (!path || !selectedNodeId.value) {
        emit('update:modelValue', null);
        return;
    }

    emit('update:modelValue', {
        node_id: selectedNodeId.value,
        path,
    });
}
</script>

<template>
    <div v-if="nodes.length" class="t-small">
        <HeadlessSelect
            :model-value="selectedNodeId"
            :options="nodeOptions"
            button-class="select-input mt-3 w-full min-w-[8rem]"
            placeholder="Выберите ноду"
            @update:model-value="onNodeSelect"
        />

        <div
            v-if="availableFields.length"
            class="mt-3"
        >
            <HeadlessSelect
                :model-value="selectedField"
                :options="fieldOptions"
                button-class="select-input w-full min-w-[8rem]"
                placeholder="Выберите поле"
                @update:model-value="onFieldSelect"
            />
        </div>
    </div>
</template>
