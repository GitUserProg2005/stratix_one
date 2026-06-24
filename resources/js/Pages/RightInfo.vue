<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Graph3D from '@/Components/Graph3D.vue';

const workflowId = computed(() => usePage().props.workflow?.id ?? null);
const flowId = computed(() =>
    workflowId.value ? `workflow-${workflowId.value}` : null,
);
</script>

<template>
    <aside class="flex h-full min-h-0 flex-col overflow-hidden rounded-3xl bg-body pl-2 pr-4 pt-4">
        <Graph3D v-if="flowId" :key="flowId" :flow-id="flowId" class="h-1/2 min-h-0 w-full shrink-0" />

        <div
            v-else
            class="flex flex-1 items-center justify-center px-3 text-center text-sm text-gray-400"
        >
            Откройте workflow для просмотра 3D-графа
        </div>
    </aside>
</template>
