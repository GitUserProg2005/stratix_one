<script setup>
import Avatar from '@/Components/Avatar.vue';

defineProps({
    participants: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div v-if="participants.length" class="dashboard-inset hidden max-w-[11rem] flex-col gap-2 sm:flex">
        <div class="flex flex-row items-center gap-2">
            <i class="fa-solid fa-users"></i>
            <div class="dashboard-row-title mb-1">Участники</div>
        </div>
        
        <div class="mt-1 flex flex-col gap-2">
            <div
                v-for="participant in participants"
                :key="participant.id"
                class="flex items-center gap-2"
            >
                <span
                    class="h-2 w-2 shrink-0 rounded-full"
                    :style="{ backgroundColor: participant.color }"
                />
                <Avatar :name="participant.name" :src="participant.avatar_url" :user-id="participant.id" size="sm" no-link />
                <span class="text-sm truncate">{{ participant.name }}</span>
            </div>
        </div>
    </div>
</template>
