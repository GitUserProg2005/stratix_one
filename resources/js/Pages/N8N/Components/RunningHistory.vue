<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import Circle from '@/Components/Skeleton/Circle.vue';
import Rectangle from '@/Components/Skeleton/Rectangle.vue';

const props = defineProps({
    workflowId: {
        type: Number,
        required: true,
    },
});

const runs = ref([]);
const isLoading = ref(true);
const expandedRunId = ref(null);
let echoChannel = null;

async function getRuns(workflowId) {
    isLoading.value = true;
    try {
        const response = await axios.get(route('get.runs', workflowId));

        if (response.data.result === 'ok') {
            runs.value = response.data.runs;
        }
    } catch (error) {
        console.error('Error fetching runs:', error);
    } finally {
        isLoading.value = false;
    }
}

function toggleRun(runId) {
    expandedRunId.value = expandedRunId.value === runId ? null : runId;
}

function statusLabel(status) {
    if (status === 'done') {
        return 'Успешно';
    }

    if (status === 'failed') {
        return 'Ошибка';
    }

    return 'Ожидание';
}

function statusClass(status) {
    if (status === 'done') {
        return 'badge-completed';
    }

    if (status === 'failed') {
        return 'badge-pending';
    }

    return 'badge-in-progress';
}

onMounted(() => {
    getRuns(props.workflowId);

    if (typeof window.Echo === 'undefined') {
        return;
    }

    echoChannel = window.Echo.private(`workflow-completed.${props.workflowId}`)
        .listen('WorkflowCompleted', (e) => {
            if (Number(e.workflowId) !== Number(props.workflowId)) {
                return;
            }

            runs.value.unshift(e.run);
        });
});

onBeforeUnmount(() => {
    if (echoChannel && typeof window.Echo !== 'undefined') {
        window.Echo.leave(`private-workflow-completed.${props.workflowId}`);
    }
});
</script>

<template>
    <div class="relative flex h-full w-full min-h-0 flex-col overflow-hidden rounded-2xl backdrop-blur-xl">
        <div class="shrink-0 border-b border-black/5 px-3 py-3 dark:border-white/10">
            <h3 class="title-2">История запусков</h3>
            <p class="t-mini mt-1">Последние запуски</p>
        </div>

        <div class="min-h-0 flex-1 space-y-2 overflow-y-auto no-scrollbar px-3 py-3">
            <div v-if="isLoading" class="space-y-2" aria-busy="true" aria-label="Загрузка истории">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="dashboard-inset flex items-center gap-3 p-3"
                >
                    <Circle size="1.75rem" />
                    <div class="min-w-0 flex-1 space-y-2">
                        <Rectangle height="0.875rem" width="70%" rounded="rounded-md" />
                        <Rectangle height="0.625rem" width="45%" rounded="rounded-md" />
                    </div>
                    <div class="shrink-0 space-y-2">
                        <Rectangle height="1.25rem" width="4.5rem" rounded="rounded-full" />
                        <Rectangle height="0.625rem" width="2.5rem" rounded="rounded-md" class="ml-auto" />
                    </div>
                </div>
            </div>

            <template v-else>
                <div
                    v-for="run in runs"
                    :key="run.id"
                    class="dashboard-inset overflow-hidden"
                >
                <button
                    type="button"
                    class="flex w-full items-center gap-3 text-left"
                    @click="toggleRun(run.id)"
                >
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#e97358]/10 text-[#e97358]"
                    >
                        <i
                            class="fa-solid text-xs transition-transform duration-200"
                            :class="expandedRunId === run.id ? 'fa-chevron-up' : 'fa-chevron-down'"
                        />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="dashboard-row-title block truncate">{{ run.name }}</span>
                        <span class="t-mini block truncate">{{ run.startedAt }}</span>
                    </span>

                    <span class="shrink-0 text-right">
                        <span class="badge shrink-0" :class="statusClass(run.status)">
                            {{ statusLabel(run.status) }}
                        </span>
                        <span class="t-mini block text-[var(--accent)]">{{ run.totalTime }} ms</span>
                    </span>
                </button>

                <div
                    v-if="expandedRunId === run.id"
                    class="mt-2 space-y-1 border-t border-black/5 pt-2 dark:border-white/10"
                >
                    <div
                        v-for="(node, index) in run.nodes"
                        :key="`${run.id}-${node.title}-${index}`"
                        class="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-[#e97358]/5"
                    >
                        <span class="t-mini w-5 shrink-0 text-center opacity-40">{{ index + 1 }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm">{{ node.title }}</span>
                        <span class="badge shrink-0" :class="statusClass(node.status)">
                            {{ statusLabel(node.status) }}
                        </span>
                        <span class="t-mini shrink-0 text-[var(--accent)]">{{ node.time }} ms</span>
                    </div>
                </div>
                </div>
            </template>
        </div>
    </div>
</template>
