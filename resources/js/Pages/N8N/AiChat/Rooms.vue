<script setup>
import { onMounted, ref, watch } from 'vue';
import axios from 'axios';
import Rectangle from '@/Components/Skeleton/Rectangle.vue';

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
const isRoomsLoading = ref(true);
const showCreate = ref(false);

const loadRooms = async () => {
    isRoomsLoading.value = true;
    try {
        const { data } = await axios.get(route('ai-chat.rooms'));
        const allRooms = Array.isArray(data) ? data : [];
        const wf = Number(props.workflowId);
        rooms.value = Number.isFinite(wf)
            ? allRooms.filter((room) => room?.context == null || Number(room.context.workflow_id) === wf)
            : allRooms;

        if (!rooms.value.length) {
            emit('update:modelValue', null);
            return;
        }

        const selectedExists = rooms.value.some((room) => Number(room.id) === Number(props.modelValue));
        if (!selectedExists) {
            emit('update:modelValue', Number(rooms.value[0].id));
        }
    } catch (error) {
        console.error('Failed to load rooms', error);
        rooms.value = [];
    } finally {
        isRoomsLoading.value = false;
    }
};

const createRoom = async () => {
    const value = title.value.trim();
    if (!value || isLoading.value) {
        return;
    }

    isLoading.value = true;
    try {
        const { data: room } = await axios.post(route('ai-chat.rooms.create'), {
            title: value,
        });

        title.value = '';
        showCreate.value = false;
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

const toggleCreate = () => {
    showCreate.value = !showCreate.value;
    if (!showCreate.value) {
        title.value = '';
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
    <aside class="dashboard-inset flex h-full min-h-0 flex-col gap-2 overflow-hidden">
        <button
            type="button"
            class="dashboard-icon-slot mx-auto shrink-0 transition hover:bg-[color-mix(in_srgb,var(--accent)_22%,transparent)]"
            title="Создать сессию"
            :disabled="isLoading"
            @click="toggleCreate"
        >
            <i class="fa-solid fa-plus text-[var(--accent)]" />
        </button>

        <input
            v-if="showCreate"
            v-model="title"
            type="text"
            class="input w-full !px-2 !py-1.5 text-xs"
            placeholder="Имя"
            :disabled="isLoading"
            @keydown.enter.prevent="createRoom"
            @keydown.escape.prevent="toggleCreate"
        >

        <div
            v-if="isRoomsLoading"
            class="min-h-0 flex-1 space-y-2 overflow-y-auto no-scrollbar"
            aria-busy="true"
            aria-label="Загрузка комнат"
        >
            <Rectangle
                v-for="i in 4"
                :key="i"
                height="2.5rem"
                width="100%"
                rounded="rounded-xl"
            />
        </div>

        <div
            v-else-if="rooms.length"
            class="min-h-0 flex-1 space-y-1.5 overflow-y-auto no-scrollbar"
        >
            <button
                v-for="room in rooms"
                :key="room.id"
                type="button"
                class="room-side-btn"
                :class="{ 'room-side-btn--active': Number(modelValue) === Number(room.id) }"
                :title="room.title"
                @click="selectRoom(room.id)"
            >
                <span class="room-side-btn__text">{{ room.title }}</span>
            </button>
        </div>

        <p v-else class="t-mini text-center">
            —
        </p>
    </aside>
</template>

<style scoped>
.room-side-btn {
    @apply flex w-full items-center justify-center rounded-xl border px-1 py-2 text-xs font-medium transition;
    border-color: var(--border-input);
    background-color: transparent;
    color: var(--content-primary);
    min-height: 2.5rem;
}

.room-side-btn:hover {
    border-color: color-mix(in srgb, var(--accent) 50%, transparent);
}

.room-side-btn--active {
    border-color: var(--accent);
    background-color: color-mix(in srgb, var(--accent) 18%, transparent);
}

.room-side-btn__text {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
