<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import Avatar from '@/Components/Avatar.vue';
import Back from '@/Pages/Player/Back.vue';

const props = defineProps({
    chat: { type: Object, default: null },
    otherUser: { type: Object, required: true },
    initialMessages: { type: Array, default: () => [] },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);

const messages = ref([...props.initialMessages]);
const localChat = ref(props.chat);
const newMessage = ref('');
const isSubmitting = ref(false);
let echoChannel = null;

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('tracks.index'));
    }
}

function subscribeToChat() {
    if (!localChat.value?.id || !window.Echo) return;
    echoChannel = window.Echo.private(`chat.${localChat.value.id}`)
        .listen('.message.sent', (e) => {
            if (e.message && !messages.value.find((m) => m.id === e.message.id)) {
                messages.value.push(e.message);
            }
        });
}

function unsubscribeFromChat() {
    if (echoChannel && window.Echo) {
        window.Echo.leave(`chat.${localChat.value?.id}`);
        echoChannel = null;
    }
}

async function sendMessage() {
    const body = newMessage.value.trim();
    if (!body || isSubmitting.value || !props.otherUser?.id) return;

    isSubmitting.value = true;
    try {
        const { data } = await axios.post(route('chat.messages.store'), {
            recipient_id: props.otherUser.id,
            body,
        });
        newMessage.value = '';
        if (data.message) {
            if (!messages.value.find((m) => m.id === data.message.id)) {
                messages.value.push(data.message);
            }
        }
        if (data.chat && !localChat.value) {
            localChat.value = data.chat;
            subscribeToChat();
        }
    } catch (err) {
        const msg = err.response?.data?.error || 'Не удалось отправить сообщение';
        alert(msg);
    } finally {
        isSubmitting.value = false;
    }
}

function formatTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const now = new Date();
    if (d.toDateString() === now.toDateString()) {
        return d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    }
    return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' }) + ' ' +
        d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
}

onMounted(() => {
    if (localChat.value?.id) {
        subscribeToChat();
    }
});

onBeforeUnmount(() => {
    unsubscribeFromChat();
});
</script>

<template>
    <AppLayout>
        <div class="w-full min-h-screen  flex flex-col">
            <div class="sticky top-0 z-20 w-full px-4 pt-4 pb-2 flex items-center gap-4 bg-body border-b border-white/5">
                <Back @back="goBack" />
                <div class="flex items-center gap-3 min-w-0">
                    <Avatar
                        :name="otherUser.name"
                        :src="otherUser.avatar_url"
                        :userId="otherUser.id"
                        size="sm"
                    />
                    <h1 class="title text-base truncate">{{ otherUser.name }}</h1>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scroll px-4 py-4">
                <div v-if="messages.length === 0" class="flex flex-col items-center justify-center py-12 context">
                    <i class="fa-solid fa-comments text-4xl mb-3 opacity-50"></i>
                    <p>Нет сообщений. Напишите первым.</p>
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        :class="[
                            'flex gap-2',
                            msg.user_id === currentUser?.id ? 'flex-row-reverse' : 'flex-row'
                        ]"
                    >
                        <div class="flex-shrink-0">
                            <Avatar
                                :name="msg.user?.name"
                                :src="msg.user?.avatar_url"
                                :userId="msg.user_id"
                                no-link
                            />
                        </div>
                        <div
                            :class="[
                                'max-w-[75%] rounded-2xl px-4 py-2',
                                msg.user_id === currentUser?.id
                                    ? 'bg-primary text-white'
                                    : 'bg-content'
                            ]"
                        >
                            <template v-if="msg.shareable">
                                <a
                                    v-if="msg.shareable.type === 'Snippet' && msg.shareable.snippet"
                                    :href="route('reels.index', { snippetId: msg.shareable.snippet.id })"
                                    class="flex flex-col gap-2 p-2 hover:bg-black/20 rounded-xl transition-colors mb-1"
                                >
                                    <img
                                        v-if="msg.shareable.snippet.track?.preview_url"
                                        :src="msg.shareable.snippet.track.preview_url"
                                        :alt="msg.shareable.snippet.track?.title"
                                        class="w-32 rounded-lg object-cover"
                                    />
                                    <i v-else class="fa-solid fa-music text-lg opacity-70"></i>
                                    <span class="text-sm font-medium truncate">
                                        {{ msg.shareable.snippet.track?.title || 'Сниппет' }}
                                    </span>
                                    <i class="fa-solid fa-play text-xs opacity-70 text-right"></i>
                                </a>
                            </template>
                            <p v-if="msg.body" class="text-sm break-words">{{ msg.body }}</p>
                            <p
                                :class="[
                                    'text-xs mt-1',
                                    msg.user_id === currentUser?.id ? 'text-white/70' : 'context'
                                ]"
                            >
                                {{ formatTime(msg.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 w-full p-4 bg-body border-t border-white/5">
                <form @submit.prevent="sendMessage" class="flex items-center gap-2">
                    <input
                        v-model="newMessage"
                        type="text"
                        placeholder="Сообщение..."
                        class="flex-1 search-input text-white placeholder-gray-500"
                        :disabled="isSubmitting"
                        maxlength="5000"
                    />
                    <button
                        v-if="newMessage.trim()"
                        type="submit"
                        class="icon-btn"
                        :disabled="!newMessage.trim() || isSubmitting"
                        aria-label="Отправить"
                    >
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
