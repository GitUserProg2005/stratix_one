<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import JsonTree from '@/Components/AI/JsonTree.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import Text from '@/Components/Skeleton/Text.vue';
import { Head } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import axios from 'axios';

const inputText = ref('');
const isLoading = ref(false);
const messages = ref([]);
const messagesContainer = ref(null);
const jsonMode = ref('false');
const jsonModeOptions = [
    { value: 'false', label: 'false' },
    { value: 'true', label: 'true' },
];

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const sendMessage = async () => {
    const text = inputText.value.trim();
    if (!text || isLoading.value) {
        return;
    }

    messages.value.push({
        role: 'user',
        text: jsonMode.value === 'true' ? `${text}\n\n[JSON mode: ON]` : text,
    });

    inputText.value = '';
    isLoading.value = true;
    await scrollToBottom();

    try {
        const { data } = await axios.post(route('ai-chat.prompt-response'), {
            text,
            json_mode: jsonMode.value === 'true',
        });

        messages.value.push({
            role: 'ai',
            text: typeof data.ai_response === 'string' ? data.ai_response : '',
            jsonMode: Boolean(data.json_mode),
            jsonData: data.json_mode ? (data.ai_response ?? {}) : null,
        });
    } catch (_error) {
        messages.value.push({
            role: 'ai',
            text: 'Ошибка при запросе к AI. Попробуйте еще раз.',
            jsonMode: false,
            jsonData: null,
        });
    } finally {
        isLoading.value = false;
        await scrollToBottom();
    }
};
</script>

<template>
    <Head title="AI Chat" />

    <DashboardLayout>
        <div class="max-w-4xl mx-auto px-4 lg:px-6 pb-6">
            <h1 class="title mb-4">AI Chat</h1>

            <div class="bg-white border border-black/10 rounded-2xl shadow-sm overflow-hidden">
                <div
                    ref="messagesContainer"
                    class="h-[480px] overflow-y-auto p-4 space-y-3 bg-[#fafafa]"
                >
                    <div
                        v-if="messages.length === 0"
                        class="text-sm text-gray-500"
                    >
                        Напишите сообщение, чтобы начать чат.
                    </div>

                    <div
                        v-for="(message, index) in messages"
                        :key="index"
                        class="flex"
                        :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[80%] px-4 py-2 rounded-2xl text-sm whitespace-pre-wrap break-words"
                            :class="message.role === 'user'
                                ? 'bg-[#e97358] text-white rounded-br-md'
                                : 'bg-white text-[#1a1a1a] border border-black/10 rounded-bl-md'"
                        >
                            <template v-if="message.role === 'ai' && message.jsonMode">
                                <div class="bg-red-500 rounded-xl p-3">
                                    <JsonTree
                                        :value="message.jsonData"
                                        node-key="response"
                                    />
                                </div>
                            </template>
                            <template v-else>
                                {{ message.text }}
                            </template>
                        </div>
                    </div>

                    <div
                        v-if="isLoading"
                        class="flex justify-start"
                        aria-live="polite"
                        aria-busy="true"
                    >
                        <div class="max-w-[75%] rounded-2xl rounded-bl-md border border-black/10 bg-white p-4">
                            <Text :lines="3" line-height="0.75rem" last-line-width="55%" />
                        </div>
                    </div>
                </div>

                <form
                    class="p-3 border-t border-black/10 flex flex-col gap-3 md:flex-row md:items-center"
                    @submit.prevent="sendMessage"
                >
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 select-none md:min-w-[190px]">
                        <span class="font-semibold">JSON mode</span>
                        <HeadlessSelect
                            v-model="jsonMode"
                            :options="jsonModeOptions"
                            button-class="select-input w-[108px]"
                            placeholder="false"
                            :disabled="isLoading"
                        />
                    </label>

                    <input
                        v-model="inputText"
                        type="text"
                        class="input flex-1"
                        placeholder="Введите сообщение..."
                        :disabled="isLoading"
                    >

                    <button
                        type="submit"
                        class="primary-btn"
                        :disabled="isLoading || !inputText.trim()"
                    >
                        Отправить
                    </button>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
