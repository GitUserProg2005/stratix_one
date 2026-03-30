<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    nodes: {
        type: Array,
        required: true
    }
});

const emit = defineEmits([
    'update:trueNode',
    'update:falseNode'
]);

const selectedTrueNodeId = ref(null);
const selectedFalseNodeId = ref(null);

watch(selectedTrueNodeId, (val) => {
    emit('update:trueNode', val);
});

watch(selectedFalseNodeId, (val) => {
    emit('update:falseNode', val);
});
</script>

<template>
    <div class="flex items-center gap-2">
        <select v-model="selectedTrueNodeId" class="select-input min-w-[10rem] flex-1">
            <option :value="null">Условие ИСТИНА</option>
            <option 
                v-for="node in nodes"
                :key="node.id"
                :value="node.id"
            >
                {{ node.data.label }}
            </option>
        </select>

        <select v-model="selectedFalseNodeId" class="select-input min-w-[10rem] flex-1">
            <option :value="null">Условие ЛОЖЬ</option>
            <option 
                v-for="node in nodes"
                :key="node.id"
                :value="node.id"
            >
                {{ node.data.label }}
            </option>
        </select>
    </div>
</template>
