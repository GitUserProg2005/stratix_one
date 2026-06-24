<script setup>
import HeadlessSelect from '@/Components/HeadlessSelect.vue';

const props = defineProps({
    output: {
        type: Object,
        required: true
    },
    parent: {
        type: Object,
        required: false
    },
    addGroup: {
        type: Function,
        required: true
    },
    addField: {
        type: Function,
        required: true
    },
    addFileField: {
        type: Function,
        required: true
    },
    deleteElement: {
        type: Function,
        required: true
    }
});

const fieldTypes = [
    { value: '', label: 'any' },
    { value: 'string', label: 'string' },
    { value: 'integer', label: 'integer' },
    { value: 'float', label: 'float' },
    { value: 'boolean', label: 'boolean' },
    {
        value: 'file',
        label: 'file',
        icon: 'fa-solid fa-paperclip',
        tone: 'file',
    },
];
</script>

<template>
    <div v-if="output.type === 'field'" class="flex flex-wrap items-center gap-2 p-1">
        <input v-model="output.key" class="input" type="text" placeholder="Ключ" />

        <HeadlessSelect
            v-model="output.data_type"
            :options="fieldTypes"
            placeholder="тип"
            button-class="select-input min-w-[7.5rem]"
        />

        <button type="button" class="badge badge-pending" @click="deleteElement(output, parent)">-D</button>
    </div>

    <div v-else-if="output.type === 'group'" class="dashboard-inset mb-2 mt-3">
        <div class="flex items-center justify-between gap-2">
            <input v-model="output.name" class="input" type="text" placeholder="Название группы" />

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="tag t-mini" @click="addGroup(output)">+G</button>
                <button type="button" class="tag t-mini" @click="addField(output)">+F</button>
                <button type="button" class="tag t-mini" @click="addFileField(output)">+File</button>
                <button type="button" class="badge badge-pending" @click="deleteElement(output, parent)">-D</button>
            </div>
        </div>

        <div v-if="output.fields.length" class="pl-4">
            <OutputNode
                v-for="(child, index) in output.fields"
                :output="child"
                :key="index"
                :add-group="addGroup"
                :add-field="addField"
                :add-file-field="addFileField"
                :delete-element="deleteElement"
                :parent="output"
            />
        </div>

        <div v-else class="context mt-2 flex items-center justify-center">
            <span>Нет параметров...</span>
        </div>
    </div>
</template>
