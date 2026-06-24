<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import ForceGraph3D from '3d-force-graph';
import { useVueFlow } from '@vue-flow/core';
import * as THREE from 'three';

const GLOW_COLOR = 0xe97358;

const props = defineProps({
    flowId: {
        type: String,
        required: true,
    },
});

const { nodes: flowNodes, edges: flowEdges } = useVueFlow(props.flowId);

const graphContainer = ref(null);
let graphInstance = null;

const graphSignature = computed(() =>
    JSON.stringify({
        nodes: flowNodes.value.map((node) => ({
            id: node.id,
            name: node.data?.label || node.label || '',
            nodeType: node.data?.type || node.type,
        })),
        links: flowEdges.value.map((edge) => ({
            id: edge.id,
            source: edge.source,
            target: edge.target,
        })),
    }),
);

function formatGraphData() {
    return {
        nodes: flowNodes.value.map((node) => ({
            id: node.id,
            name: node.data?.label || node.label || 'Без имени',
            nodeType: node.data?.type || node.type,
        })),
        links: flowEdges.value.map((edge) => ({
            id: edge.id,
            source: edge.source,
            target: edge.target,
        })),
    };
}

function nodeColor(node) {
    return node.nodeType === 'webhook_trigger' ? '#10b981' : '#E97358';
}

function createGlowNode(node) {
    const group = new THREE.Group();
    const coreColor = new THREE.Color(nodeColor(node));

    const core = new THREE.Mesh(
        new THREE.SphereGeometry(5, 20, 20),
        new THREE.MeshBasicMaterial({ color: coreColor }),
    );

    const glow = new THREE.Mesh(
        new THREE.SphereGeometry(7.2, 20, 20),
        new THREE.MeshBasicMaterial({
            color: GLOW_COLOR,
            transparent: true,
            opacity: 0.22,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        }),
    );

    const glowOuter = new THREE.Mesh(
        new THREE.SphereGeometry(3.4, 16, 16),
        new THREE.MeshBasicMaterial({
            color: GLOW_COLOR,
            transparent: true,
            opacity: 0.08,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        }),
    );

    group.add(glowOuter);
    group.add(glow);
    group.add(core);

    return group;
}

function syncGraphSize() {
    if (!graphInstance || !graphContainer.value) {
        return;
    }

    const { clientWidth, clientHeight } = graphContainer.value;
    graphInstance.width(clientWidth).height(clientHeight);
}

function focusOnNode(node) {
    if (!graphInstance) {
        return;
    }

    const distance = 120;
    const distRatio = 1 + distance / Math.hypot(node.x, node.y, node.z);

    graphInstance.cameraPosition(
        { x: node.x * distRatio, y: node.y * distRatio, z: node.z * distRatio },
        node,
        1200,
    );
}

function initGraph() {
    if (!graphContainer.value || graphInstance) {
        return;
    }

    graphInstance = ForceGraph3D()(graphContainer.value)
        .graphData(formatGraphData())
        .backgroundColor('rgba(0, 0, 0, 0)')
        .showNavInfo(false)
        .nodeThreeObject(createGlowNode)
        .nodeColor(nodeColor)
        .nodeLabel(
            (node) =>
                `<div style="background:#0f172a;color:#fff;padding:6px 10px;border-radius:8px;border:1px solid #334155;font-size:12px;">${node.name}</div>`,
        )
        .nodeRelSize(5)
        .linkColor(() => '#ffffff')
        .linkWidth(1.5)
        .linkDirectionalParticles(3)
        .linkDirectionalParticleSpeed(0.01)
        .linkDirectionalParticleWidth(3)
        .onNodeClick(focusOnNode);

    syncGraphSize();
    maybeFitView();
}

function destroyGraph() {
    if (graphInstance) {
        graphInstance._destructor?.();
        graphInstance = null;
    }
}

let resizeObserver = null;
let hasInitialFit = false;

function maybeFitView() {
    if (!graphInstance || hasInitialFit || !flowNodes.value.length) {
        return;
    }

    graphInstance.zoomToFit(400, 40);
    hasInitialFit = true;
}

onMounted(() => {
    initGraph();

    if (graphContainer.value && typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(syncGraphSize);
        resizeObserver.observe(graphContainer.value);
    }
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    destroyGraph();
});

watch(graphSignature, () => {
    if (!graphInstance) {
        return;
    }

    graphInstance.graphData(formatGraphData());
    maybeFitView();
});
</script>

<template>
    <div class="relative h-full w-full min-h-0 overflow-hidden rounded-2xl bg-content">
        <div ref="graphContainer" class="h-full w-full touch-none" />
    </div>
</template>
