<script setup>
import GetOutputFields from '../OutputSchema/GetOutputFields.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import { computed } from 'vue';

const props = defineProps({
    nodes: {
        type: Array,
        required: false
    },  
    parent: {
        type: Object,
        required: false
    },
    condition: {
        type: Object,
        required: true
    },
    addGroup: {
        type: Function,
        required: true
    },
    addCondition: {
        type: Function,
        required: true
    },
    deleteElement: {
        type: Function,
        required: true
    }
});

console.log('ConditionNode nodes:', props.nodes);

const operatorOptions = computed(() => [
    { value: '=', label: '=' },
    { value: '!=', label: '!=' },
    { value: '>', label: '>' },
    { value: '<', label: '<' },
]);

const groupOperatorOptions = computed(() => [
    { value: 'and', label: 'И' },
    { value: 'or', label: 'ИЛИ' },
]);
</script>

<template>
    <div v-if="condition.type === 'comparison'" class="p-1">
        <div class="flex gap-2">
            <!--<input v-model="condition.left" 
                class="input rounded-xl opacity-50"
                type="text"
                disabled="true"
                placeHolder="Предыдущая нода"
            >-->

            <GetOutputFields 
                :nodes="nodes"
                v-model="condition.left"
            />
            
            <HeadlessSelect
                v-model="condition.operator"
                :options="operatorOptions"
                button-class="select-input min-w-[4.5rem]"
                placeholder="Оператор"
            />

            <input v-model="condition.right" class="input" type="text" placeholder="С чем сравнивается" />

            <button type="button" class="badge badge-pending" title="Удалить" @click="deleteElement(condition, parent)">-D</button>
        </div>
    </div>

    <div v-else-if="condition.type === 'group'" class="dashboard-inset mb-2 mt-3">
        <div class="mb-2 flex items-center gap-2">
            <HeadlessSelect
                v-model="condition.op"
                :options="groupOperatorOptions"
                button-class="select-input min-w-[6rem]"
                placeholder="Оператор"
            />

            <div class="flex flex-wrap gap-2">
                <button type="button" class="tag t-mini" title="Добавить группу" @click="addGroup('and', condition)">+G</button>

                <button
                    type="button"
                    class="tag t-mini"
                    title="Добавить условие"
                    @click="addCondition({ left: '', operator: '=', right: '' }, condition)"
                >
                    +C
                </button>

                <button type="button" class="badge badge-pending" title="Удалить" @click="deleteElement(condition, parent)">-D</button>
            </div>
        </div>

        <div class="pl-4">
            <ConditionNode 
                v-for="(child, index) in condition.conditions || []"
                :key="index"            
                :condition="child"
                :add-group="addGroup"
                :add-condition="addCondition"
                :delete-element="deleteElement"
                :parent="condition"
                :nodes="nodes"
            />
        </div>
    </div>
</template>