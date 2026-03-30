<script setup>
import { ref, watch } from 'vue';

import ConditionNode from './ConditionNode.vue';
import ConditionResultNodes from './ConditionResultNodes.vue';

const props = defineProps({
    nodes: Array,
    modelValue: Object
});

const emit = defineEmits(['update:modelValue']);

const condition = ref(props.modelValue ?? {
    type: 'group',
    op: 'and',
    conditions: []
});

function addGroup(op, targetGroup=null) {
    const newGroup = {
        type: 'group',
        op: op,
        conditions: []
    };
     
    // Become the ROOT
    if (!condition.value) {
        condition.value = newGroup
        console.log('CONDITION: ', condition.value);
        return
    }

    // Become the child for the GROUP-PARENT
    if (targetGroup.type === 'group') {
        targetGroup.conditions.push(newGroup);
        return;
    }

    // Become the CHILD-ROOT for the COMPARISON
    if (condition.value.type === 'comparison') {
        condition.value = {
            type: 'group',
            op,
            conditions: [condition.value, newGroup]
        };
        return;
    }

    // Become the CHILD for the ROOT
    condition.value.conditions.push(newGroup);

    console.log('CONDITION: ', condition.value);
}

function addCondition({ left, operator, right }, targetGroup = null) {
    const comparison = {
        type: 'comparison',
        left,
        operator,
        right
    };

    if (!condition.value) {
        condition.value = comparison;

        console.log('CONDITION: ', condition.value);
        return;
    }

    if (targetGroup?.type === 'group') {
        targetGroup.conditions.push(comparison);

        console.log('CONDITION: ', condition.value);
        return
    }

    condition.value = {
        type: 'group',
        op: 'and',
        conditions: [
            structuredClone(condition.value),
            comparison
        ]
    }

    console.log('CONDITION: ', condition.value);
}

function deleteElement(target, parentGroup=null) {
    if (!parentGroup) {
        condition.value = null;
        return;
    }

    const index = parentGroup.conditions.indexOf(target);
    if (index > -1) {
        parentGroup.conditions.splice(index, 1);
        return;
    }
}

function setTrueNode(nodeId) {
    condition.value.on_true = {
        node_id: nodeId
    };
}

function setFalseNode(nodeId) {
    condition.value.on_false = {
        node_id: nodeId
    };
}

watch(condition, (val) => {
    emit('update:modelValue', val);
}, { deep: true });
</script>

<template>
    <ConditionResultNodes
        :nodes="nodes"
        @update:trueNode="setTrueNode"
        @update:falseNode="setFalseNode"
    />

    <ConditionNode 
        :condition="condition" 
        :add-group="addGroup"
        :add-condition="addCondition"
        :delete-element="deleteElement"
        :nodes="nodes"
    />
</template>