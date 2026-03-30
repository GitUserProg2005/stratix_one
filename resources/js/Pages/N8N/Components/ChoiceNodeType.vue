<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const emit = defineEmits(['onSelectedNodeType']);

const nodeTypes = ref([]);

async function getNodeTypes() {
    try {
        const response = await axios.get(route('get.node.types'));
        if (response.data.result === 'ok') {
            nodeTypes.value = response.data.nodeTypes;
        }
    } catch (error) {
        console.error('Error fetching node types:', error);
    }
}

function selectNodeType(type) {
    emit('onSelectedNodeType', type);
}

onMounted(() => {
    getNodeTypes();
});
</script>

<template>
    <div>
        <h3 class="title-2">Доступные типы</h3>

        <div v-if="nodeTypes.length" class="mt-3 flex flex-wrap items-center gap-2">
            <button
                v-for="availNodeType in nodeTypes"
                :key="availNodeType.type"
                type="button"
                class="tag t-mini"
                @click="selectNodeType(availNodeType.type)"
            >
                {{ availNodeType.name }}
            </button>
        </div>
    </div>
</template>
