<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useVueFlow } from '@vue-flow/core';

const props = defineProps({
    workflowId: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['remote-node-position', 'update:participants']);

const { onNodeDrag } = useVueFlow();
const currentUser = usePage().props.auth.user;

const colors = ['#6656f5', '#f556c7', '#f55d56'];

const activeUsers = ref([]);
const remoteCursors = ref({});

watch(activeUsers, (users) => emit('update:participants', users), { deep: true });

let channel = null;
let lastCursorTime = 0;
let lastDragTime = 0;
let lastMouse = { x: 0, y: 0 };

function withColor(user, index) {
    return { ...user, color: colors[index % colors.length] };
}

function colorForUserId(userId) {
    return activeUsers.value.find((u) => u.id === userId)?.color ?? colors[0];
}

function emitPresenceData(e) {
    if (!channel) return;

    lastMouse = { x: e.clientX, y: e.clientY };

    const now = Date.now();
    if (now - lastCursorTime < 40) return;

    lastCursorTime = now;

    channel.whisper('cursor-moving', {
        id: currentUser.id,
        name: currentUser.name,
        x: e.clientX,
        y: e.clientY,
    });
}

onNodeDrag((e) => {
    if (!channel) return;

    const now = Date.now();
    if (now - lastDragTime < 40) return;

    lastDragTime = now;

    channel.whisper('cursor-moving', {
        id: currentUser.id,
        name: currentUser.name,
        x: lastMouse.x,
        y: lastMouse.y,
        drag_node_id: e.node.id,
        node_x: e.node.position.x,
        node_y: e.node.position.y,
    });
});

onMounted(() => {
    channel = window.Echo.join(`workflow-presence.${props.workflowId}`)
        .here((users) => {
            activeUsers.value = users.map((user, index) => withColor(user, index));
        })
        .joining((user) => {
            activeUsers.value.push(withColor(user, activeUsers.value.length));
        })
        .leaving((user) => {
            activeUsers.value = activeUsers.value.filter((u) => u.id !== user.id);
            delete remoteCursors.value[user.id];
        })
        .listenForWhisper('cursor-moving', (e) => {
            if (e.id === currentUser.id) return;

            remoteCursors.value[e.id] = {
                x: e.x,
                y: e.y,
                name: e.name,
                color: colorForUserId(e.id),
            };

            if (e.drag_node_id != null && e.node_x != null && e.node_y != null) {
                emit('remote-node-position', {
                    nodeId: e.drag_node_id,
                    x: e.node_x,
                    y: e.node_y,
                });
            }
        });

    window.addEventListener('mousemove', emitPresenceData);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', emitPresenceData);
    Echo.leave(`workflow-presence.${props.workflowId}`);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-for="(cursor, userId) in remoteCursors"
            :key="userId"
            class="fixed pointer-events-none transition-all duration-75 ease-linear z-[9999]"
            :style="{ left: cursor.x + 'px', top: cursor.y + 'px' }"
        >
            <svg
                class="w-5 h-5 fill-current"
                :style="{ color: cursor.color, filter: `drop-shadow(0 0 8px ${cursor.color})` }"
                viewBox="0 0 24 24"
            >
                <path d="M7 2l12 11.2-5.8.8 3.6 6-2.4 1.4-3.6-6-3.8 3.6z"/>
            </svg>

            <div
                class="text-white text-[10px] px-1.5 py-0.5 rounded-md shadow-md font-semibold mt-1 whitespace-nowrap border border-white/10"
                :style="{ backgroundColor: cursor.color }"
            >
                {{ cursor.name }}
            </div>
        </div>
    </Teleport>
</template>
