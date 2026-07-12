<script setup>
import { ref } from 'vue';
import { VueFlow, useVueFlow } from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import '@vue-flow/core/dist/style.css';
import IntegrationFlowNode from '@/Components/Lending/IntegrationFlowNode.vue';

const flowId = 'integration-flow';

const nodeTypes = {
    integration: IntegrationFlowNode,
};

const nodes = ref([
    {
        id: 'webhook',
        type: 'integration',
        position: { x: 0, y: 10 },
        data: { title: 'WEBHOOK', desc: 'Принимает', highlight: 'JSON' },
    },
    {
        id: 'callback-1',
        type: 'integration',
        position: { x: 240, y: -10 },
        data: { title: 'CALLBACK', desc: 'Отправляет', highlight: 'ответ' },
    },
    {
        id: 'cron',
        type: 'integration',
        position: { x: 0, y: 130 },
        data: { title: 'CRON', desc: 'Запуск по', highlight: 'расписанию' },
    },
    {
        id: 'callback-2',
        type: 'integration',
        position: { x: 240, y: 150 },
        data: { title: 'CALLBACK', desc: 'Отправляет', highlight: 'ответ' },
    },
]);

const edges = ref([
    {
        id: 'webhook-callback',
        source: 'webhook',
        target: 'callback-1',
        animated: true,
        style: { stroke: '#e97358', strokeWidth: 2 },
    },
    {
        id: 'cron-callback',
        source: 'cron',
        target: 'callback-2',
        animated: true,
        style: { stroke: '#e97358', strokeWidth: 2 },
    },
]);

const { onInit } = useVueFlow(flowId);

onInit((instance) => {
    instance.fitView({ padding: 0.35, maxZoom: 1 });
});
</script>

<template>
    <section class="mt-16">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 items-center">
            <div>
                <img
                    src="/img/lending/integration.png"
                    class="w-full object-contain rounded-2xl"
                    alt="Интеграция через webhook и callback"
                >
            </div>

            <div>
                <h2 class="title">Интегрируй систему со своим приложением</h2>

                <p class="context mt-4">
                    Подключайте сценарии к своему продукту через входящие webhook, cron и callback —
                    без лишней обвязки и с полным контролем над данными.
                </p>

                <div class="relative mt-8 h-[17rem] rounded-2xl overflow-hidden bg-content-glass integration-flow">
                    <span class="absolute left-1/2 top-1/2 z-10 -translate-x-1/2 -translate-y-1/2 text-sm font-semibold text-[#e97358] pointer-events-none">
                        ИЛИ
                    </span>

                    <VueFlow
                        :id="flowId"
                        v-model:nodes="nodes"
                        v-model:edges="edges"
                        :node-types="nodeTypes"
                        :nodes-connectable="false"
                        :elements-selectable="false"
                        :zoom-on-scroll="false"
                        :zoom-on-pinch="false"
                        :pan-on-scroll="false"
                        :prevent-scrolling="true"
                        class="h-full w-full"
                    >
                        <Background variant="dots" :gap="18" :size="1.5" color="rgba(120,120,152,0.18)" />
                    </VueFlow>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.integration-flow :deep(.vue-flow) {
    background: transparent;
}

.integration-flow :deep(.vue-flow__panel),
.integration-flow :deep(.vue-flow__controls),
.integration-flow :deep(.vue-flow__minimap) {
    display: none;
}

.integration-flow :deep(.integration-flow-handle) {
    width: 0.45rem;
    height: 0.45rem;
    background: #e97358;
    border: none;
}
</style>
