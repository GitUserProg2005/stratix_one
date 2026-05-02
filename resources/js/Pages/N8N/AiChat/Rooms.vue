<script setup>
import { onMounted, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    workflowId: {
        type: [Number, String],
        required: true,
    },
    modelValue: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const rooms = ref([]);
const title = ref('');
const isLoading = ref(false);

const loadRooms = async () => {
    const { data } = await axios.get(route('ai-chat.rooms'));
    const allRooms = Array.isArray(data) ? data : [];
    const wf = Number(props.workflowId);
    rooms.value = Number.isFinite(wf)
        ? allRooms.filter((room) => room?.context != null && Number(room.context.workflow_id) === wf)
        : [];

    if (!rooms.value.length) {
        emit('update:modelValue', null);
        return;
    }

    const selectedExists = rooms.value.some((room) => Number(room.id) === Number(props.modelValue));
    if (!selectedExists) {
        emit('update:modelValue', Number(rooms.value[0].id));
    }
};

const createRoom = async () => {
    const value = title.value.trim();
    if (!value || isLoading.value) {
        return;
    }

    isLoading.value = true;
    try {
        const { data: context } = await axios.post(route('ai-chat.context.create'), {
            context: `workflow_id: ${props.workflowId}`,
            workflow_id: props.workflowId,
        });

        const { data: room } = await axios.post(route('ai-chat.rooms.create'), {
            title: value,
            context_id: context?.id ?? null,
        });

        title.value = '';
        await loadRooms();
        if (room?.id) {
            emit('update:modelValue', Number(room.id));
        }
    } catch (error) {
        console.error('Failed to create room', error);
    } finally {
        isLoading.value = false;
    }
};

const selectRoom = (id) => {
    emit('update:modelValue', Number(id));
};

watch(() => props.workflowId, () => {
    loadRooms();
});

onMounted(() => {
    loadRooms();
});
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <input
                v-model="title"
                type="text"
                class="input w-full"
                placeholder="Название комнаты"
                :disabled="isLoading"
                @keydown.enter.prevent="createRoom"
            >
            <button
                type="button"
                class="primary-btn whitespace-nowrap"
                :disabled="isLoading || !title.trim()"
                @click="createRoom"
            >
                Создать
            </button>
        </div>

        <div class="overflow-x-auto pb-1">
            <div class="flex min-w-max items-center gap-2">
                <button
                    v-for="room in rooms"
                    :key="room.id"
                    type="button"
                    class="rounded-xl border px-3 py-1.5 text-sm transition"
                    :class="Number(modelValue) === Number(room.id)
                        ? 'border-[var(--accent)] bg-[rgba(233,115,88,0.18)] text-[var(--content-primary)]'
                        : 'border-[var(--border-input)] bg-content-glass text-[var(--content-primary)] hover:border-[var(--accent)]/50'"
                    @click="selectRoom(room.id)"
                >
                    {{ room.title }}
                </button>
            </div>
        </div>
    </div>
</template>
