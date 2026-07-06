<script setup>
import SchemaTree from './SchemaTree.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import { computed } from 'vue';

const props = defineProps({
    schema: Object,
    prefix: {
        type: String,
        default: '',
    },
    mappings: Object,
    sourceFields: Array,
    arraySourceFields: Array,
});

const emit = defineEmits(['update']);

const path = computed(() => {
    const name = props.schema?.name || props.schema?.key;

    if (!name) {
        return props.prefix || '';
    }

    return props.prefix
        ? `${props.prefix}.${name}`
        : name;
});

const label = computed(() => {
    return props.schema?.name || props.schema?.key || '';
});

function buildOptions(fields) {
    const options = [{ value: '', label: 'Не выбрано' }];

    for (const source of fields || []) {
        options.push({ value: source, label: source });
    }

    return options;
}

const sourceOptions = computed(() => buildOptions(props.sourceFields));

// только пути массивов — для выбора источника всего массива
const arraySourceOptions = computed(() => buildOptions(props.arraySourceFields));

function update(target, source) {
    emit('update', { target, source });
}
</script>

<template>
    <div v-if="schema?.type === 'group'" class="ml-2 space-y-2">
        <span v-if="label" class="mb-2 block">{{ label }}</span>

        <SchemaTree
            v-for="(field, index) in schema.fields || []"
            :key="field?.name || field?.key || index"
            :schema="field"
            :prefix="path"
            :mappings="mappings"
            :source-fields="sourceFields"
            :array-source-fields="arraySourceFields"
            @update="$emit('update', $event)"
        />
    </div>

    <div v-else-if="schema?.type === 'array'" class="ml-2 space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <span>{{ label }}[]</span>
            <span class="badge badge-in-progress">массив</span>
        </div>

        <!-- источник всего массива -->
        <HeadlessSelect
            :model-value="mappings?.[path] ?? ''"
            :options="arraySourceOptions"
            button-class="select-input mt-1 w-full"
            placeholder="Источник массива"
            @update:model-value="(value) => update(path, value)"
        />

        <!-- структура элемента -->
        <SchemaTree
            :schema="schema.items"
            :prefix="`${path}[]`"
            :mappings="mappings"
            :source-fields="sourceFields"
            :array-source-fields="arraySourceFields"
            @update="$emit('update', $event)"
        />
    </div>

    <div v-else-if="schema?.type === 'field'" class="ml-2 space-y-1">
        <span class="mb-2 block">{{ label }}</span>

        <HeadlessSelect
            :model-value="mappings?.[path] ?? ''"
            :options="sourceOptions"
            button-class="select-input mt-2 w-full"
            placeholder="Не выбрано"
            @update:model-value="(value) => update(path, value)"
        />
    </div>
</template>
