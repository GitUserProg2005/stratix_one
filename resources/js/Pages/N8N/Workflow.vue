<script setup>
import Modal from '@/Components/Modal.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateNode from './Components/CreateNode.vue';
import CustomNode from './Components/CustomNode.vue';
import CustomEdge from './Components/CustomEdge.vue';
import BackButton from '@/Components/BackButton.vue';
import MapEnvironment from './Components/MapEnvironment.vue';
import { Link } from '@inertiajs/vue3';
import BottomPanel from './Components/BottomPanel.vue';
import RedDot from './Components/RedDot.vue';

import { useNodeSchemas } from './composables/useNodeSchemas';

import { VueFlow, useVueFlow, Panel } from '@vue-flow/core';
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import { Background } from '@vue-flow/background';

import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

const props = defineProps({
    workflow: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['delete']);

const bottomPanelComponent = ref(null);
const bottomPanelProps = ref({});

/** Один store на строку workflow: тот же id, что у <VueFlow> (@vue-flow/core не экспортирует VueFlowProvider). */
const vueFlowInstanceId = `workflow-${props.workflow.id}`;

const { schemas } = useNodeSchemas();

const { addNodes, project } = useVueFlow(vueFlowInstanceId);

const workflowIsRunning = ref(false);
const workflowFailed = ref(null);

const nodeTypes = { custom: CustomNode };
const edgeTypes = { custom: CustomEdge };

const showLogsModal = ref(false);
const logs = ref([]);
const countLogs = ref(0);

const nodes = ref([]);
const edges = ref([]);
const isLoading = ref(false);

let workflowEchoChannel = null;

function leaveWorkflowChannel() {
    if (typeof window.Echo === 'undefined' || !props.workflow?.id) {
        return;
    }

    const name = `workflow-step.${props.workflow.id}`;

    try {
        window.Echo.leave(`private-${name}`);
    } catch {
        try {
            window.Echo.leave(name);
        } catch {
            /* noop */
        }
    }

    workflowEchoChannel = null;
}

function subscribeWorkflowChannel() {
    if (typeof window.Echo === 'undefined') {
        return;
    }

    leaveWorkflowChannel();

    workflowEchoChannel = window.Echo.private(`workflow-step.${props.workflow.id}`)
        .listen('WorkflowStep', async (e) => {
            if (e.result && String(e.result).trim() !== '') {
                const cleanResult = String(e.result).replace(/\\n/g, '\n');
                const existingLog = logs.value.find((log) => log.nodeId === e.currentNodeId);

                if (existingLog) {
                    existingLog.body += cleanResult;
                } else {
                    logs.value.push({
                        nodeId: e.currentNodeId,
                        body: `INFO ${e.nextProcessingNodeId || e.currentNodeId}:\n\n${cleanResult}`,
                    });
                }

                countLogs.value += 1;
            }

            const currentId = String(e.currentNodeId);
            const nextId = e.nextProcessingNodeId ? String(e.nextProcessingNodeId) : null;

            nodes.value = nodes.value.map((node) =>
                node.id === currentId
                    ? { ...node, data: { ...node.data, status: 'running' } }
                    : node
            );
            nodes.value = [...nodes.value];

            await new Promise((r) => setTimeout(r, 2000));

            nodes.value = nodes.value.map((node) =>
                node.id === currentId
                    ? { ...node, data: { ...node.data, status: 'done' } }
                    : node
            );

            nodes.value = [...nodes.value];

            if (nextId) {
                nodes.value = nodes.value.map((node) =>
                    node.id === nextId
                        ? { ...node, data: { ...node.data, status: 'running' } }
                        : node
                );
                nodes.value = [...nodes.value];
            } else {
                workflowIsRunning.value = false;
            }
        })
    .listen('WorkflowFailed', (e) => {
        console.log('WORKFLOW FAILED: ', e);

        nodes.value = nodes.value.map((node) => 
            node.id === String(e.currentNodeId)
                ? {...node, data: {
                    ...node.data, status: 'failed'
                }}
                : node
        );
        nodes.value = [...nodes.value];

        logs.value.push({
            nodeId: e.currentNodeId,
            body: e.error
        });
        countLogs.value++;

        console.log(nodes.value.filter(node => node.id === String(e.currentNodeId)));

        workflowIsRunning.value = false;
        workflowFailed.value.errorText = e.error;
    });
}

function toggleLogsModal() {
    showLogsModal.value = !showLogsModal.value;
}

async function getNodes() {
    isLoading.value = true;
    try {
        const response = await axios.get(route('get.nodes', props.workflow.id));
        if (response.data.result === 'ok') {
            nodes.value = response.data.nodes.map((node) => ({
                id: String(node.id),
                type: 'custom',
                position: node.position || { x: 100, y: 100 },
                data: {
                    id: node.id,
                    label: node.title,
                    type: node.type,
                    config: node.config || {},
                    status: 'idle',
                },
            }));
        }
    } catch (e) {
        console.error('Ошибка загрузки нод:', e);
    } finally {
        isLoading.value = false;
    }
}

function handleCreatedNode(node) {
    const position = project(node.position);

    addNodes({
        id: String(node.id),
        type: 'custom',
        position,
        data: {
            id: node.id,
            label: node.title,
            type: node.type,
            config: node.config || {},
        },
    });
}

function handleUpdatedNode(updatedNode) {
    nodes.value = nodes.value.map((node) =>
        node.id === String(updatedNode.id)
            ? {
                  ...node,
                  data: {
                      ...node.data,
                      label: updatedNode.title,
                      type: updatedNode.type,
                      config: updatedNode.config,
                  },
              }
            : node
    );
}

function handleDeletedNode(deletedNodeId) {
    const deletedId = String(deletedNodeId);
    nodes.value = nodes.value.filter((node) => node.id !== deletedId);
    edges.value = edges.value.filter(
        (e) => e.source !== deletedId && e.target !== deletedId
    );
}

function onNodeDragStop({ node }) {
    axios
        .post(route('update.node.position', node.id), { position: node.position })
        .catch((e) => console.error('Ошибка обновления позиции', e));
}

async function onConnect({ source, target }) {
    const tempId = `${source}-${target}`;
    edges.value.push({
        id: tempId,
        source,
        target,
        type: 'custom',
        animated: true,
    });
    try {
        const response = await axios.post(route('create.edge'), {
            workflow_id: props.workflow.id,
            source_node_id: parseInt(source, 10),
            target_node_id: parseInt(target, 10),
            type: 'custom',
        });
        if (response.data.result === 'ok' && response.data.edge) {
            const edge = response.data.edge;
            edges.value = edges.value.map((e) =>
                e.id === tempId
                    ? {
                          id: String(edge.id),
                          source: String(edge.source_node_id),
                          target: String(edge.target_node_id),
                          type: edge.type || 'custom',
                          animated: true,
                          data: edge.data || {},
                      }
                    : e
            );
        }
    } catch (e) {
        console.error('Ошибка создания связи:', e);
        edges.value = edges.value.filter((e) => e.id !== tempId);
    }
}

async function getEdges() {
    try {
        const response = await axios.get(route('get.edges', props.workflow.id));
        if (response.data.result === 'ok') {
            edges.value = response.data.edges.map((edge) => ({
                id: String(edge.id),
                source: String(edge.source_node_id),
                target: String(edge.target_node_id),
                type: edge.type || 'custom',
                animated: true,
                data: {
                    ...edge.data,
                    transform: edge.transform || {}
                },
            }));
        }
    } catch (e) {
        console.error('Ошибка загрузки рёбер:', e);
    }
}

function getNodeById(id) {
    return nodes.value.find(n => n.id === String(id));
}

function getSchema(node) {
    if (!node) return null;
    return schemas.value[node.data.type];
}

function toggleIsRunning() {
    workflowIsRunning.value = !workflowIsRunning.value;
    if (workflowIsRunning.value) {
        workflowObserve();
    }
}

async function workflowObserve() {
    nodes.value = nodes.value.map((node) => ({
        ...node,
        data: { ...node.data, status: 'idle' },
    }));
    
    try {
        await axios.post(route('run.workflow', props.workflow.id));
    } catch (e) {
    console.error('Ошибка запуска workflow:', e);
        workflowIsRunning.value = false;
    }
}

function openBottomPanel({ component, props }) {
    bottomPanelComponent.value = component;
    bottomPanelProps.value = props;
}

function closeBottomPanel() {
  bottomPanelComponent.value = null;
}

onMounted(() => {
    logs.value = [];
    countLogs.value = 0;
    getNodes();
    getEdges();
    subscribeWorkflowChannel();
});

onBeforeUnmount(() => {
    leaveWorkflowChannel();
    workflowIsRunning.value = false;
});
</script>

<template>  
    <AppLayout>
        <div class="relative">
            <div class="px-4 md:px-6 flex flex-row items-center gap-3 mt-4 mb-4">
                <BackButton :backUrl="'workflows.index'" />

                <h2 class="title-2">
                    <Link>Workflows </Link>
                    <span> / </span>
                    <Link>{{ workflow.name }}</Link>
                </h2>
            </div>

        <div class="px-4 md:px-6 flex flex-col custom-scroll">
            <div
                class="dashboard-chart-wrap relative flex w-full flex-col !h-auto min-h-[80vh]"
            >
                <VueFlow
                    :id="vueFlowInstanceId"
                    :key="`${vueFlowInstanceId}-${nodes.length}`"

                    v-model:nodes="nodes"
                    v-model:edges="edges"

                    :node-types="nodeTypes"
                    :edge-types="edgeTypes"

                    class="h-full w-full flex-1"

                    @node-drag-stop="onNodeDragStop"
                    @connect="onConnect"
                >
                    <Background variant="dots" :gap="15" :size="2" color="rgba(120,120,152,0.13)" />

                    <template #node-custom="{ data }">
                        <CustomNode
                            :nodes="nodes"
                            :data="data"
                            :schemas="schemas"
                            :workflow-id="workflow.id"
                            :on-webhook-log="
                                (log) => {
                                    logs.push(log);
                                    countLogs += 1;
                                }
                            "
                            @node-updated="handleUpdatedNode"
                            @node-deleted="handleDeletedNode"
                            @open-bottom-panel="openBottomPanel"
                        />
                    </template>

                    <template #edge-custom="edgeProps">
                        <CustomEdge
                            v-bind="edgeProps"
                            
                            :source-node="getNodeById(edgeProps.source)"
                            :target-node="getNodeById(edgeProps.target)"

                            :source-schema="getSchema(getNodeById(edgeProps.source))"
                            :target-schema="getSchema(getNodeById(edgeProps.target))"

                            :edge="edges.find(e => e.id === edgeProps.id)"

                            @add-mapping="handleAddMapping"
                        />
                    </template>

                    <Panel position="top-left" class="!m-3 flex max-w-[min(100%,28rem)] flex-col gap-2">
                        <div class="dashboard-inset flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="primary-btn flex items-center gap-2 text-sm"
                                @click="toggleIsRunning"
                            >
                                Запустить
                                <i v-if="!workflowIsRunning" class="fa-solid fa-play"></i>
                                <span
                                    v-if="workflowIsRunning"
                                    class="inline-block h-4 w-4 rounded-full border-2 border-white border-t-transparent animate-spin"
                                />
                            </button>

                            <CreateNode :workflow-id="workflow.id" :nodes="nodes" @onCreatedNode="handleCreatedNode" />
                        </div>
                        <div class="dashboard-inset hidden max-w-[11rem] sm:block">
                            <div class="dashboard-row-title mb-1">Индикаторы</div>

                            <div class="flex items-center gap-2">
                                <RedDot />
                                <span class="t-mini">Заполните параметры</span>
                            </div>
                        </div>
                    </Panel>

                    <Panel position="bottom-center" class="!m-3 w-[min(100%,calc(100%-1.5rem))] max-w-none">
                        <div
                            class="dashboard-inset cursor-pointer"
                            :class="showLogsModal ? 'max-h-48' : 'max-h-14 overflow-hidden'"
                            @dblclick="toggleLogsModal"
                        >
                            <div class="flex items-center gap-2">
                                <span class="dashboard-row-title text-sm">Результаты</span>
                                <span class="badge badge-completed">{{ countLogs }}</span>
                            </div>
                            <div v-if="showLogsModal" class="custom-scroll mt-2 max-h-36 space-y-2 overflow-y-auto">
                                <pre
                                    v-for="log in logs"
                                    :key="log.nodeId"
                                    class="dashboard-inset t-mini whitespace-pre-wrap"
                                    >{{ log.body }}</pre
                                >
                                <p v-if="!logs.length" class="context">Пока нет логов</p>
                            </div>
                        </div>
                    </Panel>
                </VueFlow>
            </div>
        </div>

        <BottomPanel
                :component="bottomPanelComponent"
                :componentProps="bottomPanelProps"
                @close="closeBottomPanel"
            />
        </div>
    </AppLayout>
</template>
