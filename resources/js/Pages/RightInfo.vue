<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Graph3D from '@/Pages/N8N/Components/Graph3D.vue';
import RunningHistory from '@/Pages/N8N/Components/RunningHistory.vue';

const workflowId = computed(
    () => usePage().props.workflow?.id ?? usePage().props.dashboard?.workflow?.id ?? null,
);
const flowId = computed(() =>
    workflowId.value ? `workflow-${workflowId.value}` : null,
);
</script>

<template>
    <aside class="flex h-full min-h-0 w-full flex-col overflow-hidden">
        <section
            v-if="flowId"
            class="h-1/2 min-h-0 w-full bg-content-glass rounded-2xl shrink-0 overflow-hidden"
        >
            <Graph3D :key="flowId" :flow-id="flowId" class="size-full" />
        </section>

        <section
            v-if="workflowId"
            class="h-1/2 min-h-0 w-full shrink-0 overflow-hidden pt-2"
        >
            <RunningHistory :workflow-id="workflowId" class="size-full" />
        </section>

        <div
            v-if="!flowId"
            class="h-full flex flex-1 flex-col items-center justify-center px-3 text-center text-sm text-gray-400"
        >

            <p class="text-sm text-gray-400 mt-4 ">Откройте workflow для просмотра 3D-графа</p>
        </div>
    </aside>
</template>
