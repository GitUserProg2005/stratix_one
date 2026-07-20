<script setup>
import { computed, ref } from 'vue';

import Avatar from '@/Components/Avatar.vue';
import TaskCard from '@/Pages/Task/Card.vue';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
    currentUserId: {
        type: [Number, String],
        default: null,
    },
});

const isOpened = ref(false);

const emit = defineEmits(['edit', 'confirm']);

const difficultyLabel = {
    easy: 'Легкая',
    normal: 'Средняя',
    hard: 'Сложная',
};

function taskDifficulty(task) {
    return typeof task.difficulty === 'object' ? task.difficulty?.value : task.difficulty;
}

function taskStatus(task) {
    return typeof task.status === 'object' ? task.status?.value : task.status;
}

function taskDifficultyColor(task) {
    switch (taskDifficulty(task)) {
        case 'easy':
            return '#10b981';
        case 'normal':
            return '#f59e0b';
        case 'hard':
            return '#ef4444';
        default:
            return '#6b7280';
    }
}

// Кнопка подтверждения только для completed (ещё не finalized)
const needsConfirm = computed(() => taskStatus(props.task) === 'completed');

function toggleOpened() {
    isOpened.value = !isOpened.value;
}
</script>

<template>
    <div
        class="dashboard-inset space-y-2 cursor-grab active:cursor-grabbing"
    >
        <div @dblclick="emit('edit', task)">
            <div class="opacity-70">
                <span class="context">
                    <i class="fa-brands fa-diaspora text-sm mr-2" />
                    {{ task.project.title }}
                </span>
            </div>

            <div class="title-3 mt-4">{{ task.title }}</div>

            <div class="flex items-center gap-2 context pt-4">
                <span>Сложность:</span>
                <span
                    class="w-2 h-2 rounded-full shrink-0"
                    :style="{ backgroundColor: taskDifficultyColor(task) }"
                />
                <span>
                    {{ difficultyLabel[taskDifficulty(task)] ?? taskDifficulty(task) }}
                </span>
            </div>
        </div>

        <div v-if="needsConfirm" class="pt-2">
            <button
                type="button"
                class="primary-btn-white-blur flex w-full items-center justify-center gap-2 text-sm"
                @click.stop="emit('confirm', task)"
            >
                Подтвердить
                <i class="fa-solid fa-check" />
            </button>
        </div>

        <div v-if="task.workers?.length" class="flex flex-wrap items-center gap-2">
            <div
                v-for="worker in task.workers"
                :key="worker.id"
                :title="worker.name"
            >
                <div class="badge-neutral flex items-center gap-2">
                    <Avatar
                        :name="worker.name"
                        :src="worker.avatar_url"
                        :user-id="worker.id"
                        :no-link="true"
                        size="sm"
                    />
                    <div class="w-1 h-1 bg-white rounded-full shrink-0" />
                    <span class="context">
                        {{ worker.name }}
                        <span v-if="Number(worker.id) === Number(currentUserId)"> (Вы)</span>
                    </span>
                </div>
            </div>

            <div v-if="task.children?.length" class="flex items-center gap-2">
                <button type="button" class="dashboard-icon-slot transition hover:bg-[color-mix(in_srgb,#ef4444_18%,transparent)] disabled:opacity-50" 
                @click="toggleOpened">
                    <i class="fa-solid fa-chevron-down text-[var(--accent)]" />
                </button>
            </div>
        </div>

        <div v-if="task.children?.length && isOpened" class="mt-2">
            <div class="flex gap-2">
                <div
                    class="w-4 shrink-0 self-stretch bg-white/20 [clip-path:polygon(0_0,2px_0,2px_calc(100%-2px),100%_calc(100%-2px),100%_100%,0_100%)]
                    
                    "
                    aria-hidden="true"
                />

                <div class="min-w-0 flex-1 space-y-2 mt-2">
                    <TaskCard
                        v-for="child in task.children"
                        :key="child.id"
                        :task="child"
                        :current-user-id="currentUserId"
                        @edit="emit('edit', $event)"
                        @confirm="emit('confirm', $event)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
