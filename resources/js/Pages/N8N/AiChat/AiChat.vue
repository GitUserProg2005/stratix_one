<script setup>
import Messages from './Messages.vue';
import Rooms from './Rooms.vue';
import Context from './Context.vue';
import { ref } from 'vue';

defineProps({
    workflowId: {
        type: [Number, String],
        required: true,
    },
});

const selectedRoomId = ref(null);

function setRoomId(id) {
    selectedRoomId.value = id !== null && id !== undefined ? Number(id) : null;
}
</script>

<template>
    <div class="bg-content-glass grid h-full min-h-0 grid-cols-1 gap-4 md:grid-cols-2">
        <div class="flex h-full min-h-0 flex-col border-r-2 border-gray-500/20 p-4">
            <h2 class="title-2 shrink-0">AI-агент</h2>

            <Messages
                class="min-h-0 flex-1"
                :room-id="selectedRoomId"
                :workflow-id="workflowId"
            />
        </div>

        <div class="grid h-full min-h-0 grid-cols-[minmax(0,9fr)_minmax(3.5rem,1fr)] gap-2 overflow-hidden p-4">
            <Context
                class="min-h-0 overflow-hidden"
                :workflow-id="workflowId"
                :room-id="selectedRoomId"
            />

            <Rooms
                class="min-h-0 overflow-hidden"
                :model-value="selectedRoomId"
                :workflow-id="workflowId"
                @update:model-value="setRoomId"
            />
        </div>
    </div>
</template>

<script>
export default {
    bottomPanel: {
        name: 'Workflow-AGENT',
        height: '450px',
    }
};
</script>
