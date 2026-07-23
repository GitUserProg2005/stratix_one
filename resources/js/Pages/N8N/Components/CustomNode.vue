<script setup>
import UpdateNode from './UpdateNode.vue';
import RedDot from './RedDot.vue';
import MapEnvironment from './MapEnvironment.vue';
import { Handle, Position } from '@vue-flow/core';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { nodeConfigFields } from './nodeConfigFields';
import { createButtonHandler } from './nodeButtonHandlers';
import IOSchemas from './IOSchemas/IOSchemas.vue';

const props = defineProps({
    nodes: Array,
    data: Object,
    schemas: Object,
    schemasLoading: {
        type: Boolean,
        default: false,
    },
    workflowId: Number,
    onWebhookLog: Function,
    modelValue: Object,
    lock: Object,
    acquireLock: Function,
    releaseLock: Function,
});

const emit = defineEmits(['nodeUpdated', 'nodeDeleted',
    'open-bottom-panel',
]);

const showUpdateModal = ref(false);
const isWebhookLoading = ref(false);
const currentUserId = usePage().props.auth.user?.id;

const isLockedByOther = computed(() =>
    props.lock && props.lock.userId !== currentUserId
);

const lockStyle = computed(() =>
    props.lock ? { boxShadow: `0 0 0 3px ${props.lock.color}` } : {}
);

function onDblClick() {
    if (isLockedByOther.value) return;
    if (props.acquireLock && !props.acquireLock()) return;
    showUpdateModal.value = true;
}

function closeModal() {
    props.releaseLock?.();
    showUpdateModal.value = false;
}

const nodeConfig = nodeConfigFields[props.data.type] || { buttons: [] };
const buttons = nodeConfig.buttons || [];

async function handleButtonClick(buttonConfig) {
    const handler = createButtonHandler(
        buttonConfig.key,
        props.workflowId,
        props.data.id,
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
            'content-badge-pending !p-4': data.status === 'failed',
            'pointer-events-none opacity-60': isLockedByOther,
        }"
        :style="lockStyle"
        @dblclick="onDblClick"
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
            <img v-if="data.type === 'webhook_trigger'" src="/img/nodes/webhook.png" 
                class="w-8 object-contain select-none pointer-events-none" alt="Gigachat"
            >
            <img v-if="data.type === 'osrm'" src="/img/nodes/osrm.png" 
                class="w-8 object-contain select-none pointer-events-none" alt="Gigachat"
            >
            <img v-if="data.type === 'go_whisper'" src="/img/nodes/go-whisper.png" 
                class="w-8 object-contain select-none pointer-events-none" alt="Gigachat"
            >
            <img v-if="data.type === 'mistral_text'" src="/img/nodes/mistral_text.png"
                class="w-12 h-12 object-contain select-none pointer-events-none" alt="Mistral Text"
            >
            <img v-if="data.type === 'mistral_picture'" src="/img/nodes/pixtral.png"
                class="w-12 h-12 object-contain select-none pointer-events-none" alt="Mistral Picture"
            >
            <img v-if="data.type === 'mistral_ocr'" src="/img/nodes/mistral_ocr.png"
                class="w-12 h-12 object-contain select-none pointer-events-none" alt="Mistral OCR"
            >
            <img v-if="data.type === 'point_in_polygon'" src="/img/nodes/point_in_polygon.png"
                class="w-12 h-12 object-contain select-none pointer-events-none" alt="Mistral OCR"
            >
            <img v-if="data.type === 'http_callback'" src="/img/nodes/callback.png"
                class="w-12 h-12 object-contain select-none pointer-events-none" alt="Mistral OCR"
            >
            <img v-if="data.type === 'update_metric'" src="/img/nodes/update_metric.png"
                class="w-12 h-12 object-contain select-none pointer-events-none" alt="Mistral OCR"
            >
            <img v-if="data.type === 'condition'" src="/img/nodes/condition.png"
                class="w-12 h-12 object-contain select-none pointer-events-none" alt="Mistral OCR"
            >
            <i
                v-if="data.type === 'task_trigger'"
                class="fa-solid fa-bars-progress text-xl mt-1 select-none pointer-events-none"
                style="color: #e97358"
            />
            <i
                v-if="data.type === 'to_string'"
                class="fa-solid fa-align-left text-xl mt-1 select-none pointer-events-none"
                style="color: #e97358"
            />

            <div class="flex min-w-0 flex-col gap-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="truncate text-sm font-semibold">{{ data.label }}</h3>
                    <span v-if="data.status === 'idle'" class="badge-neutral">{{ data.status }}</span>
                    <span v-else-if="data.status === 'running'" class="badge badge-in-progress">{{ data.status }}</span>
                    <span v-else-if="data.status === 'done'" class="badge badge-completed">{{ data.status }}</span>
                    <span v-else-if="data.status === 'failed'" class="badge badge-pending">{{ data.status }}</span>
                </div>
                <span class="t-mini truncate">{{ data.type }}</span>
            </div>
        </div>

        <div class="absolute bottom-2 right-3">
            <i v-if="data.status === 'done'" class="fa-solid fa-check text-accent" />
        </div>

        <!--<div class="absolute -bottom-3 right-1/2 translate-x-1/2 label-accent">
            <button @click.stop="() => emit('open-bottom-panel', 
                { component: MapEnvironment, props: { node: data } })"
            >
                <i class="fa-regular fa-map"></i>
            </button>
        </div>-->

        <div class="absolute -top-9 right-3 label-content">
            <IOSchemas :schemas="schemas" :node="data" :node-type="data.type" :schemas-loading="schemasLoading" />
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
        :workflow-id="workflowId"
        @close="closeModal"
        @onUpdatedNode="(updatedNode) => { closeModal(); emit('nodeUpdated', updatedNode); }"
        @onDeletedNode="(deletedNodeId) => { closeModal(); emit('nodeDeleted', deletedNodeId); }"
    />
</template>
