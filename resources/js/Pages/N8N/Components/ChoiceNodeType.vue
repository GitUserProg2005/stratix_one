<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Rectangle from '@/Components/Skeleton/Rectangle.vue';

const emit = defineEmits(['onSelectedNodeType']);

const nodeTypes = ref([]);
const isLoading = ref(true);

async function getNodeTypes() {
    isLoading.value = true;
    try {
        const response = await axios.get(route('get.node.types'));
        if (response.data.result === 'ok') {
            nodeTypes.value = response.data.nodeTypes;
        }
    } catch (error) {
        console.error('Error fetching node types:', error);
    } finally {
        isLoading.value = false;
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

        <div v-if="isLoading" class="mt-3 flex flex-wrap items-center gap-2" aria-busy="true" aria-label="Загрузка типов">
            <Rectangle
                v-for="i in 6"
                :key="i"
                height="2rem"
                width="5.5rem"
                rounded="rounded-full"
            />
        </div>

        <div v-else-if="nodeTypes.length" class="mt-3 flex flex-wrap items-center gap-2">
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
