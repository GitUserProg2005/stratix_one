<script setup>
import { ref, watch, toRaw } from 'vue';

import OutputNode from './OutputNode.vue';

const props = defineProps({
    modelValue: Object,
    title: String
});

const output = ref(props.modelValue ?? {
    type: 'group',
    name: '',
    fields: [
        {
            type: 'field',
            key: '',
            data_type: '',
        }
    ]
});

const emit = defineEmits(['update:modelValue']);

function addGroup(targetGroup=null) {
    const newGroup = {
        type: 'group',
        name: '',
        fields: []
    };

    if (!output.value) {
        output.value = newGroup;
        return;
    }

    if (targetGroup.type === 'group') {
        targetGroup.fields.push(newGroup);
        return;
    }

    if (output.value.type === 'field') {
        output.value = {
            type: 'group',
            name: '',
            fields: [output.value, newGroup]
        };
        return;
    }

    output.value.fields.push(newGroup);
}

function addField(targetGroup=null) {
    const field = {
        type: 'field',
        key: '',
        data_type: 'string',
        required: true,
    };

    if (!output.value) {
        output.value = field;
        return;
    }

    if (targetGroup?.type === 'group') {
        targetGroup.fields.push(field);
        return;
    }

    output.value = {
        type: 'group',
        name: '',
        fields: [
            structuredClone(toRaw(output.value)),
            field
        ]
    };
}

function addFileField(targetGroup=null) {
    const field = {
        type: 'field',
        key: '',
        data_type: 'file',
        required: true,
    };

    if (!output.value) {
        output.value = field;
        return;
    }

    if (targetGroup?.type === 'group') {
        targetGroup.fields.push(field);
        return;
    }

    output.value = {
        type: 'group',
        name: '',
        fields: [
            structuredClone(toRaw(output.value)),
            field
        ]
    };
}

function deleteElement(target, parentGroup=null) {
    if (!parentGroup) {
        output.value = null;
        return;
    }

    const index = parentGroup.fields.indexOf(target);
    if (index > -1) {
        parentGroup.fields.splice(index, 1);
        return;
    }
}


watch(output, (val) => {
    emit('update:modelValue', val);
}, { deep: true })
</script>

<template>
    <OutputNode 
        :output="output"
        :add-group="addGroup"
        :add-field="addField"
        :add-file-field="addFileField"
        :delete-element="deleteElement"
    />
</template>