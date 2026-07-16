<script setup>
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import Text from '@/Components/Skeleton/Text.vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';

marked.use({
    breaks: true,
    gfm: true,
});

function renderAiMarkdown(source) {
    const raw = typeof source === 'string' ? source : '';
    const html = marked.parse(raw);

    return DOMPurify.sanitize(html);
}

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
const isMessagesLoading = ref(false);

const isProcessing = ref(false);
const messagesContainer = ref(null);

const graphSyncHint = ref('');
const copiedMessageId = ref(null);

let workflowDiffChannel = null;
let copiedTimer = null;

async function copyMessageText(message) {
    const text = typeof message?.text === 'string' ? message.text : '';
    if (!text) {
        return;
    }

    try {
        await navigator.clipboard.writeText(text);
        copiedMessageId.value = message.id;
        if (copiedTimer) {
            clearTimeout(copiedTimer);
        }
        copiedTimer = setTimeout(() => {
            copiedMessageId.value = null;
        }, 1500);
    } catch (error) {
        console.error('Failed to copy message', error);
    }
}

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

    isMessagesLoading.value = true;
    try {
        const { data } = await axios.get(route('ai-chat.messages', { room: id }));
        messages.value = Array.isArray(data) ? data : [];
    } catch (error) {
        console.error('Failed to load messages', error);
        messages.value = [];
    } finally {
        isMessagesLoading.value = false;
    }
};

const sendMessage = async () => {
    const text = inputText.value.trim();
    const id = resolvedRoomId.value;
    if (!text || isLoading.value || isProcessing.value || !id) {
        return;
    }

    isLoading.value = true;
    isProcessing.value = true;
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
        isProcessing.value = false;
    }
};

watch(messages, () => {
    scrollToBottom();
}, { deep: true });

watch(isProcessing, async (on) => {
    if (on) {
        await scrollToBottom();
    }
});

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
    if (copiedTimer) {
        clearTimeout(copiedTimer);
    }
});
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <p v-if="graphSyncHint" class="mb-2 rounded-lg bg-[rgba(233, 115, 88,0.15)] px-2 py-1 text-xs text-[var(--content-primary)]">
            {{ graphSyncHint }}
        </p>

        <div
            ref="messagesContainer"
            class="custom-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1 pb-2"
        >
            <p v-if="!resolvedRoomId" class="context">
                Создайте комнату и выберите ее справа.
            </p>

            <div
                v-else-if="isMessagesLoading"
                class="space-y-3"
                aria-busy="true"
                aria-label="Загрузка сообщений"
            >
                <div class="flex w-full justify-end">
                    <div class="max-w-[70%] rounded-2xl rounded-br-sm bg-content-glass p-3">
                        <Text :lines="2" line-height="0.625rem" last-line-width="50%" />
                    </div>
                </div>
                <div class="flex w-full justify-start">
                    <div class="max-w-[75%] rounded-2xl rounded-bl-sm bg-content-glass p-3">
                        <Text :lines="4" line-height="0.625rem" last-line-width="40%" />
                    </div>
                </div>
            </div>

            <p v-else-if="!messages.length && !isProcessing" class="context">
                Сообщений пока нет. Напишите первое сообщение.
            </p>

            <template v-if="!isMessagesLoading && resolvedRoomId">
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="flex w-full"
                    :class="roleIsAi(message) ? 'justify-start' : 'justify-end'"
                >
                <div
                    class="group/msg flex max-w-[82%] flex-col gap-2"
                    :class="roleIsAi(message) ? 'items-stretch' : 'items-end'"
                >
                    <div
                        class="relative rounded-2xl px-3 py-2 text-sm break-words"
                        :class="roleIsAi(message)
                            ? 'bg-content-glass text-[var(--content-primary)] rounded-bl-sm'
                            : 'bg-[var(--accent)] text-white rounded-br-sm whitespace-pre-wrap'"
                    >
                        <button
                            type="button"
                            class="absolute right-1.5 top-1.5 z-10 flex h-7 w-7 items-center justify-center rounded-lg opacity-0 transition group-hover/msg:opacity-100"
                            :class="roleIsAi(message)
                                ? 'bg-black/10 text-[var(--content-secondary)] hover:bg-black/15 hover:text-[var(--accent)] dark:bg-white/10 dark:hover:bg-white/15'
                                : 'bg-white/15 text-white/80 hover:bg-white/25 hover:text-white'"
                            :title="copiedMessageId === message.id ? 'Скопировано' : 'Копировать'"
                            @click="copyMessageText(message)"
                        >
                            <i
                                class="text-xs"
                                :class="copiedMessageId === message.id ? 'fa-solid fa-check' : 'fa-regular fa-copy'"
                            />
                        </button>

                        <div
                            v-if="roleIsAi(message)"
                            class="prose prose-sm prose-neutral max-w-none pr-8 dark:prose-invert prose-p:my-1.5 prose-headings:mb-2 prose-headings:mt-3 prose-headings:text-[var(--content-primary)] prose-p:text-[var(--content-primary)] prose-li:text-[var(--content-primary)] prose-strong:text-[var(--content-primary)] prose-a:text-[var(--accent)] prose-a:no-underline hover:prose-a:underline prose-pre:max-h-64 prose-pre:overflow-auto prose-pre:rounded-lg prose-pre:border prose-pre:border-[var(--border-input)] prose-pre:bg-black/[0.08] dark:prose-pre:bg-white/[0.06] prose-code:rounded prose-code:px-1 prose-code:py-0.5 prose-code:text-[var(--content-primary)] prose-code:before:content-none prose-code:after:content-none prose-code:bg-black/10 dark:prose-code:bg-white/10"
                            v-html="renderAiMarkdown(message.text)"
                        />
                        <template v-else>
                            <span class="pr-8">{{ message.text }}</span>
                        </template>
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
            </template>

            <div
                v-if="isProcessing"
                class="flex w-full justify-start"
                aria-live="polite"
                aria-busy="true"
            >
                <div class="relative max-w-[82%] overflow-hidden rounded-2xl rounded-bl-sm bg-content-glass px-3 py-3">
                    <div
                        class="pointer-events-none absolute inset-0 overflow-hidden"
                        aria-hidden="true"
                    >
                        <div
                            class="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-r from-transparent via-white/25 to-transparent dark:via-white/10 animate-shimmer-slide"
                        />
                    </div>
                    <div class="relative flex items-center gap-2.5 text-sm text-[var(--content-primary)]">
                        <i
                            class="fa-solid fa-spinner fa-spin shrink-0 text-base text-[var(--accent)]"
                            aria-hidden="true"
                        />
                        <span>Обрабатываю...</span>
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
