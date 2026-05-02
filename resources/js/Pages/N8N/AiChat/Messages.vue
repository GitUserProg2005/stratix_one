<script setup>
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    roomId: {
        type: [Number, String],
        default: null,
    },
    workflowId: {
        type: [Number, String],
        default: null,
    },
});

const emits = defineEmits(['workflow-updated']);

const resolvedRoomId = computed(() => {
    if (props.roomId === null || props.roomId === undefined || props.roomId === '') {
        return null;
    }
    const n = Number(props.roomId);

    return Number.isFinite(n) ? n : null;
});

const resolvedWorkflowId = computed(() => {
    if (props.workflowId === null || props.workflowId === undefined || props.workflowId === '') {
        return null;
    }
    const n = Number(props.workflowId);

    return Number.isFinite(n) ? n : null;
});

const mode = ref('ask');
const modeOptions = [
    { value: 'ask', label: 'ASK' },
    { value: 'agent', label: 'AGENT' },
    { value: 'plan', label: 'PLAN' },
];

const messages = ref([]);
const inputText = ref('');
const isLoading = ref(false);
const messagesContainer = ref(null);

const graphSyncHint = ref('');

let workflowDiffChannel = null;

function roleIsAi(message) {
    const r = message?.role;
    if (r === 'ai' || r === 'AI') {
        return true;
    }
    if (typeof r === 'object' && r !== null) {
        return String(r.value ?? r) === 'ai';
    }

    return false;
}

function leaveWorkflowDiffChannel() {
    if (typeof window.Echo === 'undefined' || resolvedWorkflowId.value === null) {
        return;
    }

    const name = `workflow-diff-applied.${resolvedWorkflowId.value}`;
    try {
        window.Echo.leave(`private-${name}`);
    } catch {
        try {
            window.Echo.leave(name);
        } catch {
            /* noop */
        }
    }
    workflowDiffChannel = null;
}

function subscribeWorkflowDiffChannel() {
    if (typeof window.Echo === 'undefined' || resolvedWorkflowId.value === null) {
        return;
    }

    leaveWorkflowDiffChannel();

    workflowDiffChannel = window.Echo
        .private(`workflow-diff-applied.${resolvedWorkflowId.value}`)
        .listen('WorkflowDiffApplied', (e) => {
            if (Number(e.workflowId) !== Number(resolvedWorkflowId.value)) {
                return;
            }
            graphSyncHint.value = 'Граф обновлён';
            window.setTimeout(() => {
                graphSyncHint.value = '';
            }, 4000);
            emits('workflow-updated', { workflowId: e.workflowId, payload: e });
        });
}

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const getMessages = async () => {
    const id = resolvedRoomId.value;
    if (!id) {
        messages.value = [];

        return;
    }

    const { data } = await axios.get(route('ai-chat.messages', { room: id }));
    messages.value = Array.isArray(data) ? data : [];
};

const sendMessage = async () => {
    const text = inputText.value.trim();
    const id = resolvedRoomId.value;
    if (!text || isLoading.value || !id) {
        return;
    }

    isLoading.value = true;
    try {
        await axios.post(route('ai-chat.process-message', { room: id }), {
            text,
            type: mode.value,
        });

        inputText.value = '';
        await getMessages();

        emits('workflow-updated', { workflowId: props.workflowId });
    } catch (error) {
        console.error('Failed to send message', error);
    } finally {
        isLoading.value = false;
    }
};

watch(messages, () => {
    scrollToBottom();
}, { deep: true });

watch(resolvedRoomId, async () => {
    try {
        await getMessages();
        await scrollToBottom();
    } catch (error) {
        console.error('Failed to load messages', error);
    }
}, { immediate: true });

watch(resolvedWorkflowId, () => {
    subscribeWorkflowDiffChannel();
});

onMounted(() => {
    subscribeWorkflowDiffChannel();
});

onBeforeUnmount(() => {
    leaveWorkflowDiffChannel();
});
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <p v-if="graphSyncHint" class="mb-2 rounded-lg bg-[rgba(233,115,88,0.15)] px-2 py-1 text-xs text-[var(--content-primary)]">
            {{ graphSyncHint }}
        </p>

        <div
            ref="messagesContainer"
            class="custom-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1 pb-2"
        >
            <p v-if="!resolvedRoomId" class="context">
                Создайте комнату и выберите ее справа.
            </p>
            <p v-else-if="!messages.length" class="context">
                Сообщений пока нет. Напишите первое сообщение.
            </p>

            <div
                v-for="message in messages"
                :key="message.id"
                class="flex w-full"
                :class="roleIsAi(message) ? 'justify-start' : 'justify-end'"
            >
                <div
                    class="flex max-w-[82%] flex-col gap-2"
                    :class="roleIsAi(message) ? 'items-stretch' : 'items-end'"
                >
                    <div
                        class="rounded-2xl px-3 py-2 text-sm whitespace-pre-wrap break-words"
                        :class="roleIsAi(message)
                            ? 'bg-content-glass text-[var(--content-primary)] rounded-bl-sm'
                            : 'bg-[var(--accent)] text-white rounded-br-sm'"
                    >
                        {{ message.text }}
                    </div>

                    <div
                        v-if="roleIsAi(message) && message.metadata?.plan_todo?.length"
                        class="rounded-xl border border-[var(--border-input)] bg-content-glass px-3 py-2"
                    >
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--content-primary)] opacity-70">
                            План
                        </div>
                        <ul class="space-y-2">
                            <li
                                v-for="(item, idx) in message.metadata.plan_todo"
                                :key="idx"
                                class="flex items-start gap-2 rounded-lg border border-white/10 bg-black/15 px-2 py-1.5 text-sm text-[var(--content-primary)]"
                                :style="{
                                    borderLeftWidth: '3px',
                                    borderLeftStyle: 'solid',
                                    borderLeftColor: item.color && /^#[0-9A-Fa-f]{6}$/.test(item.color) ? item.color : '#6b7280',
                                }"
                            >
                                <span class="shrink-0 font-mono text-xs opacity-50">{{ idx + 1 }}</span>
                                <span>{{ item.title }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <form class="mt-3" @submit.prevent="sendMessage">
            <HeadlessSelect
                v-model="mode"
                :options="modeOptions"
                button-class="min-w-[92px] rounded-xl border border-transparent bg-transparent px-3 py-2 text-xs font-semibold tracking-wide text-[#93a1b8]"
                options-class="!w-[150px] !left-0 !left-auto"
                :disabled="isLoading"
            />

            <div class="flex items-start gap-2 rounded-2xl bg-content-glass p-2">
                <textarea
                    v-model="inputText"
                    rows="2"
                    class="w-full resize-none input"
                    placeholder="Задайте вопрос на русском языке..."
                    :disabled="isLoading || !resolvedRoomId"
                    @keydown.enter.exact.prevent="sendMessage"
                />

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 primary-btn"
                    :disabled="isLoading || !inputText.trim() || !resolvedRoomId"
                >
                    <i class="fa-regular fa-paper-plane"></i>
                    Спросить
                </button>
            </div>
        </form>
    </div>
</template>
