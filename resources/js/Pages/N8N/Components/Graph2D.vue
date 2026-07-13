<script setup>
import { computed } from 'vue';
import { VNetworkGraph, defineConfigs } from 'v-network-graph';
import 'v-network-graph/lib/style.css';

const NODE_COLOR = '#e97358';
const CLUSTER_STEP = 400;
const NODE_JITTER = 130;

const props = defineProps({
    clusters: {
        type: Array,
        default: () => [],
    },
});

/** Псевдослучайное 0..1 из seed — стабильно при пересчёте computed */
function rand(seed) {
    const value = Math.sin(seed) * 10000;

    return value - Math.floor(value);
}

/** Сдвиг кластера в случайном направлении (влево/вправо/вверх/вниз) */
function clusterOffset(workflowId) {
    const seed = Number(workflowId) * 9973;
    const angle = rand(seed) * Math.PI * 2;
    const distance = CLUSTER_STEP + rand(seed + 1) * 100;

    return {
        dx: Math.cos(angle) * distance,
        dy: Math.sin(angle) * distance,
    };
}

/** Лёгкий разброс нод внутри кластера */
function nodeOffset(workflowId, nodeId, nodeIndex) {
    const seed = Number(workflowId) * 1009 + Number(nodeId) * 9176 + nodeIndex;

    return {
        x: (rand(seed) - 0.5) * NODE_JITTER,
        y: (rand(seed + 1) - 0.5) * NODE_JITTER,
    };
}

const graphData = computed(() => {
    const nodes = {};
    const edges = {};
    const layouts = { nodes: {} };

    let offsetX = 0;
    let offsetY = 0;

    props.clusters.forEach((cluster) => {
        const { dx, dy } = clusterOffset(cluster.workflow_id);
        offsetX += dx;
        offsetY += dy;

        (cluster.nodes ?? []).forEach((node, nodeIndex) => {
            const nodeId = `w${cluster.workflow_id}-n${node.id}`;
            nodes[nodeId] = {
                name: node.title || node.type || 'Узел',
            };

            const base = node.position && typeof node.position === 'object'
                ? node.position
                : { x: nodeIndex * 48, y: nodeIndex * 36 };

            const jitter = nodeOffset(cluster.workflow_id, node.id, nodeIndex);

            layouts.nodes[nodeId] = {
                x: offsetX + Number(base.x ?? 0) * 0.35 + jitter.x,
                y: offsetY + Number(base.y ?? 0) * 0.35 + jitter.y,
            };
        });

        (cluster.edges ?? []).forEach((edge) => {
            const edgeId = `w${cluster.workflow_id}-e${edge.id}`;
            const source = `w${cluster.workflow_id}-n${edge.source_node_id}`;
            const target = `w${cluster.workflow_id}-n${edge.target_node_id}`;

            if (!nodes[source] || !nodes[target]) {
                return;
            }

            edges[edgeId] = { source, target };
        });
    });

    return { nodes, edges, layouts };
});

const configs = defineConfigs({
    view: {
        autoPanAndZoomOnLoad: 'fit-content',
        fitContentMargin: '12%',
        panEnabled: true,
        zoomEnabled: true,
        minZoomLevel: 0.4,
        maxZoomLevel: 8,
    },
    node: {
        normal: {
            type: 'circle',
            radius: 5,
            color: NODE_COLOR,
            strokeWidth: 2,
            strokeColor: '#ffffff33',
        },
        label: {
            visible: false,
        },
        draggable: true,
        selectable: false,
    },
    edge: {
        normal: {
            width: 1.5,
            color: '#ffffff55',
        },
        selectable: false,
    },
});

const hasGraph = computed(() => Object.keys(graphData.value.nodes).length > 0);
</script>

<template>
    <div class="graph2d-root relative size-full min-h-[280px] overflow-hidden rounded-2xl bg-content-glass">
        <VNetworkGraph
            v-if="hasGraph"
            :nodes="graphData.nodes"
            :edges="graphData.edges"
            :layouts="graphData.layouts"
            :configs="configs"
            class="size-full"
        />
        <div
            v-else
            class="flex size-full items-center justify-center"
        >
            <p class="context text-sm opacity-60">Нет данных для графа</p>
        </div>
    </div>
</template>

<style scoped>
.graph2d-root :deep(.v-network-graph) {
    width: 100%;
    height: 100%;
}
</style>
