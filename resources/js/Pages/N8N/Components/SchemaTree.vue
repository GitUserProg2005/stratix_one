<script setup>
import SchemaTree from './SchemaTree.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import { computed } from 'vue';

const props = defineProps({
    schema: Object,
    prefix: {
        type: String,
        default: ''
    },
    mappings: Object,
    sourceFields: Array
});

const emit = defineEmits('update');

const path = computed(() => {
    const name = props.schema?.name || props.schema?.key;

    if (!name) return '';

    return props.prefix 
        ? `${props.prefix}.${name}`
        : name;
});

const label = computed(() => {
    return props.schema?.name || props.schema?.key || '';
});

const sourceOptions = computed(() => {
    const options = [{ value: '', label: 'Не выбрано' }];
    for (const source of props.sourceFields || []) {
        options.push({ value: source, label: source });
    }
    return options;
});

function update(value) {
    emit('update', {
        target: path.value,
        source: value
    });
}
</script>

<template>
    <div v-if="schema.type === 'group'" class="ml-2">
        <span class="mb-2">{{ label }}</span>

        <SchemaTree 
            v-for="field in schema.fields"
            :key="field.name || field.key"
            :schema="field"
            :prefix="path"
            :mappings="mappings"
            :sourceFields="sourceFields"
            @update="$emit('update', $event)"
        />  
    </div>

    <div v-else-if="schema.type === 'array'" class="ml-2">
        <span class="mb-2">{{ label }}[]</span>

        <SchemaTree 
            :schema="schema.items"
            :prefix="path + '[]'"
            :mappings="mappings"
            :sourceFields="sourceFields"
            @update="$emit('update', $event)"
        /> 
    </div>

    <div v-else-if="schema.type === 'field'" class="ml-2">
        <span class="mb-2">{{ label }}</span>

        <HeadlessSelect
            :model-value="mappings?.[path] ?? ''"
            :options="sourceOptions"
            button-class="select-input mt-2 w-full"
            placeholder="Не выбрано"
            @update:model-value="update"
        />
    </div>
</template>
