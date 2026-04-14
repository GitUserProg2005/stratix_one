<script setup>
import UpdateNode from './UpdateNode.vue';
import RedDot from './RedDot.vue';
import MapEnvironment from './MapEnvironment.vue';
import { Handle, Position } from '@vue-flow/core';
import { ref } from 'vue';
import { nodeConfigFields } from './nodeConfigFields';
import { createButtonHandler } from './nodeButtonHandlers';
import IOSchemas from './IOSchemas.vue';

const props = defineProps({
    nodes: Array,
    data: Object,
    schemas: Object,
    workflowId: Number,
    onWebhookLog: Function,
    modelValue: Object,
});

const emit = defineEmits(['nodeUpdated', 'nodeDeleted',
    'open-bottom-panel',
]);

const showUpdateModal = ref(false);
const isWebhookLoading = ref(false);

const nodeConfig = nodeConfigFields[props.data.type] || { buttons: [] };
const buttons = nodeConfig.buttons || [];

async function handleButtonClick(buttonConfig) {
    const handler = createButtonHandler(
        buttonConfig.key,
        props.workflowId,
        props.onWebhookLog,
        buttonConfig.requiresLoading ? isWebhookLoading : null
    );

    if (handler) {
        await handler.execute();
    }
}

function isLoadingForButton(buttonConfig) {
    return buttonConfig.requiresLoading && isWebhookLoading.value;
}
</script>

<template>
    <div
        class="relative min-w-[12rem] rounded-2xl transition-all duration-500"
        :class="{
            'dashboard-inset': data.status === 'idle' || !data.status,
            'content-badge-in-progress !p-4': data.status === 'running',
            'content-badge-completed !p-4': data.status === 'done',
        }"
        @dblclick="showUpdateModal = true"
    >
        <Handle type="target" :position="Position.Left" />

        <RedDot
            v-if="!data.config || Object.keys(data.config).length === 0"
            class="absolute -right-0 -top-1"
        />

        <div class="relative flex flex-row gap-3 pr-6">
            <img v-if="data.type === 'ai_request'" src="/img/nodes/gigachat.png" 
                class="w-12 h-12 object-contain select-none pointer-events-none" alt="Gigachat"
            >
            <img v-if="data.type === 'ai_agent_request'" src="/img/nodes/gigachat.png" 
                class="w-12 h-12 object-contain select-none pointer-events-none" alt="Gigachat"
            >
            <img v-if="data.type === 'email_report'" src="/img/nodes/yandex_mail.png" 
                class="w-12 h-12 object-contain select-none pointer-events-none" alt="Gigachat"
            >
            
            <div class="flex min-w-0 flex-col gap-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="truncate text-sm font-semibold">{{ data.label }}</h3>
                    <span v-if="data.status === 'idle'" class="badge badge-pending">{{ data.status }}</span>
                    <span v-else-if="data.status === 'running'" class="badge badge-in-progress">{{ data.status }}</span>
                    <span v-else-if="data.status === 'done'" class="badge badge-completed">{{ data.status }}</span>
                </div>
                <span class="t-mini truncate">{{ data.type }}</span>
            </div>
        </div>

        <div class="absolute bottom-2 right-3">
            <i v-if="data.status === 'done'" class="fa-solid fa-check text-accent" />
        </div>

        <div class="absolute -bottom-3 right-1/2 translate-x-1/2 label-accent">
            <button @click.stop="() => emit('open-bottom-panel', 
                { component: MapEnvironment, props: { node: data } })"
            >
                <i class="fa-regular fa-map"></i>
            </button>
        </div>

        <div class="absolute -top-5 right-3 label-accent">
            <IOSchemas :schemas="schemas" :node-type="data.type" />
        </div>

        <Handle type="source" :position="Position.Right" />
    </div>

    <!--Inline-кнопки-->
    <div v-if="buttons.length > 0" class="mt-2 flex flex-col gap-2">
        <button
            v-for="button in buttons"
            :key="button.key"
            type="button"
            class="tag t-mini flex items-center justify-center gap-1 whitespace-nowrap"
            :disabled="isLoadingForButton(button)"
            @click.stop="handleButtonClick(button)"
        >
            {{ button.label }}
            <span
                v-if="isLoadingForButton(button)"
                class="text-accent inline-block h-3 w-3 animate-spin rounded-full border-2 border-current border-t-transparent"
            />
        </button>
    </div>

    <UpdateNode
        :show="showUpdateModal"
        :node-data="data"
        :nodes="nodes"
        @close="showUpdateModal = false"
        @onUpdatedNode="(updatedNode) => emit('nodeUpdated', updatedNode)"
        @onDeletedNode="(deletedNodeId) => emit('nodeDeleted', deletedNodeId)"
    />
</template>
