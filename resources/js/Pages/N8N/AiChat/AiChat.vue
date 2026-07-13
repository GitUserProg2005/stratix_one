<script setup>
import Messages from './Messages.vue';
import Rooms from './Rooms.vue';
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

const jsonContext = {
    'some': 'data',
    'my': 123,
};
</script>

<template>
    <div class="bg-content-glass grid h-full min-h-0 grid-cols-1 gap-4 md:grid-cols-2">
        <div class="p-4 border-r-2 border-gray-500/20 h-full min-h-0">
            <h2>AI-агент</h2>

            <Messages
                :room-id="selectedRoomId"
                :workflow-id="workflowId"
            />
        </div>

        <div class="p-4 flex h-full min-h-0 flex-col gap-4 overflow-hidden">
            <Rooms
                :model-value="selectedRoomId"
                :workflow-id="workflowId"
                @update:model-value="setRoomId"
            />

            <div>
                Контекст workflow:
            </div>
            <pre>{{ 
                jsonContext
            }}</pre>
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