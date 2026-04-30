<script setup>
import { computed, ref, watch } from 'vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';

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

const trueNodeOptions = computed(() => {
    const options = [{ value: null, label: 'Условие ИСТИНА' }];
    for (const node of props.nodes) {
        options.push({ value: node.id, label: node.data.label });
    }
    return options;
});

const falseNodeOptions = computed(() => {
    const options = [{ value: null, label: 'Условие ЛОЖЬ' }];
    for (const node of props.nodes) {
        options.push({ value: node.id, label: node.data.label });
    }
    return options;
});

watch(selectedTrueNodeId, (val) => {
    emit('update:trueNode', val);
});

watch(selectedFalseNodeId, (val) => {
    emit('update:falseNode', val);
});
</script>

<template>
    <div class="flex items-center gap-2">
        <HeadlessSelect
            v-model="selectedTrueNodeId"
            :options="trueNodeOptions"
            button-class="select-input min-w-[10rem] flex-1"
            placeholder="Условие ИСТИНА"
        />

        <HeadlessSelect
            v-model="selectedFalseNodeId"
            :options="falseNodeOptions"
            button-class="select-input min-w-[10rem] flex-1"
            placeholder="Условие ЛОЖЬ"
        />
    </div>
</template>
