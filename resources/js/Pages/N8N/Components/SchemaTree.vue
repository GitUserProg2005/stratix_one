<script setup>
import SchemaTree from './SchemaTree.vue';
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

        <select
            :value="mappings[path]"
            @change="update($event.target.value)"
        >
            <option value="">Не выбрано</option>

            <option 
                v-for="source in sourceFields" 
                :key="source"
                :value="source"
            >
                {{ source }}
            </option>
        </select>
    </div>
</template>