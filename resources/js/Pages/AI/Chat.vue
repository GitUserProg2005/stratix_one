<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import axios from 'axios';

const inputText = ref('');
const isLoading = ref(false);
const messages = ref([]);
const messagesContainer = ref(null);

const scrollToBottom = async () => {
    await nextTick();

    if (!messagesContainer.value) {
        return;
    }

    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
};

const sendMessage = async () => {
    const text = inputText.value.trim();

    if (!text || isLoading.value) {
        return;
    }

    messages.value.push({
        role: 'user',
        text,
    });

    inputText.value = '';
    isLoading.value = true;
    await scrollToBottom();

    try {
        const { data } = await axios.post(route('ai-chat.prompt-response'), {
            text,
        });

        messages.value.push({
            role: 'ai',
            text: data.ai_response ?? 'Пустой ответ от AI.',
        });
    } catch (error) {
        messages.value.push({
            role: 'ai',
            text: 'Ошибка при запросе к AI. Попробуйте ещё раз.',
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
                            {{ message.text }}
                        </div>
                    </div>

                    <div
                        v-if="isLoading"
                        class="text-sm text-gray-500"
                    >
                        AI печатает...
                    </div>
                </div>

                <form
                    class="p-3 border-t border-black/10 flex gap-2"
                    @submit.prevent="sendMessage"
                >
                    <input
                        v-model="inputText"
                        type="text"
                        class="flex-1 rounded-xl border-black/20 focus:border-[#e97358] focus:ring-[#e97358]"
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
