<script setup>
import Avatar from '@/Components/Avatar.vue';

defineProps({
    members: {
        type: Array,
        required: true,
    },
    size: {
        type: String,
        default: 'sm',
    },
    noLink: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="dashboard-inset flex flex-col gap-3">
        <span class="title-3 shrink-0">Участники</span>

        <div
            v-if="members.length"
            class="flex flex-nowrap gap-2 overflow-x-auto no-scrollbar lg:flex-wrap lg:overflow-visible"
        >
            <div
                v-for="member in members"
                :key="member.id"
                class="shrink-0"
                :title="member.name"
            >
                <Avatar
                    :name="member.name"
                    :src="member.avatar_url"
                    :user-id="member.id"
                    :no-link="noLink"
                    :size="size"
                />
            </div>
        </div>

        <p v-else class="context">Выберите проект</p>
    </div>
</template>
