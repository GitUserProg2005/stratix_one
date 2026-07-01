<script setup>
import { onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    workflowId: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits([
    'nodeCreated',
    'nodeUpdated',
    'nodeDeleted',
    'edgeCreated',
    'edgeUpdated',
    'edgeDeleted',
]);

const currentUserId = usePage().props.auth.user?.id;

function handleWorkflowUpdated(e) {
    if (Number(e.workflowId) !== Number(props.workflowId)) return;
    if (Number(e.userId) === Number(currentUserId)) return;

    switch (e.action) {
        case 'node.created':
            emit('nodeCreated', e.payload);
            break;
        case 'node.updated':
            emit('nodeUpdated', e.payload);
            break;
        case 'node.deleted':
            emit('nodeDeleted', e.payload);
            break;
        case 'edge.created':
            emit('edgeCreated', e.payload);
            break;
        case 'edge.updated':
            emit('edgeUpdated', e.payload);
            break;
        case 'edge.deleted':
            emit('edgeDeleted', e.payload);
            break;
    }
}

onMounted(() => {
    window.Echo.private(`workflow-updated.${props.workflowId}`)
        .listen('WorkflowUpdated', handleWorkflowUpdated);
});

onUnmounted(() => {
    const name = `workflow-updated.${props.workflowId}`;
    try {
        window.Echo.leave(`private-${name}`);
    } catch {
        window.Echo.leave(name);
    }
});
</script>
