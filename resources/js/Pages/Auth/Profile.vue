<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import Avatar from '@/Components/Avatar.vue';
import Back from '@/Pages/Player/Back.vue';

const props = defineProps({
    user: Object,
    friendshipStatus: { type: String, default: null },
    friendshipId: { type: Number, default: null },
    likedSnippets: { type: Array, default: () => [] },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);
const isOwnProfile = computed(() => currentUser.value && currentUser.value.id === props.user.id);

const loading = ref(false);
const localStatus = ref(props.friendshipStatus);

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('tracks.index'));
    }
}

function goToChat() {
    // Заглушка: позже — переход в чат
    alert('Чат будет доступен в следующей версии.');
}

const sendRequest = async () => {
    if (!props.user?.id || loading.value) return;
    loading.value = true;
    try {
        await axios.post(route('friends.send', props.user.id));
        localStatus.value = 'pending_sent';
    } catch (e) {
        const msg = e.response?.data?.error || 'Ошибка отправки заявки';
        alert(msg);
    } finally {
        loading.value = false;
    }
};

const acceptRequest = async () => {
    if (!props.friendshipId || loading.value) return;
    loading.value = true;
    try {
        await axios.post(route('friends.accept', props.friendshipId));
        localStatus.value = 'accepted';
    } catch {
        alert('Ошибка принятия заявки');
    } finally {
        loading.value = false;
    }
};

const rejectRequest = async () => {
    if (!props.friendshipId || loading.value) return;
    loading.value = true;
    try {
        await axios.post(route('friends.reject', props.friendshipId));
        localStatus.value = null;
    } catch {
        alert('Ошибка отклонения заявки');
    } finally {
        loading.value = false;
    }
};

const status = computed(() => localStatus.value ?? props.friendshipStatus);

function openReel(snippetId) {
    router.get(route('reels.index'), { snippetId });
}
</script>

<template>
    <AppLayout>
        <div class="w-full min-h-screen bg-body">
            <!-- Шапка: кнопка назад -->
            <div class="sticky top-0 z-20 w-full px-4 pt-4 pb-2 flex items-center">
                <Back @back="goBack" />
            </div>

            <!-- Блок профиля в стиле TikTok -->
            <header class="relative w-full px-4 pt-2 pb-6">
                <div class="bg-content rounded-2xl p-6 flex flex-col items-center text-center">
                    <!-- Аватар крупно -->
                    <div class="mb-4 inline-block">
                        <Avatar
                            :name="user.name"
                            :src="user.avatar_url"
                            :userId="user.id"
                            size="lg"
                        />
                    </div>
                    <h1 class="title text-xl mb-0.5">{{ user.name }}</h1>
                    <p v-if="user.email && isOwnProfile" class="context text-xs mb-4">{{ user.email }}</p>
                    <p v-else class="context text-xs mb-4">Профиль пользователя</p>

                    <!-- Кнопки действий -->
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <template v-if="currentUser && !isOwnProfile">
                            <template v-if="status === null">
                                <button
                                    class="primary-btn"
                                    :disabled="loading"
                                    @click="sendRequest"
                                >
                                    {{ loading ? '...' : 'Добавить в друзья' }}
                                </button>
                            </template>
                            <template v-else-if="status === 'pending_sent'">
                                <span class="context">Заявка отправлена</span>
                            </template>
                            <template v-else-if="status === 'pending_received'">
                                <button
                                    class="btn-positive mr-1"
                                    :disabled="loading"
                                    @click="acceptRequest"
                                >
                                    Принять
                                </button>
                                <button
                                    class="btn-negative"
                                    :disabled="loading"
                                    @click="rejectRequest"
                                >
                                    Отклонить
                                </button>
                            </template>
                            <template v-else-if="status === 'accepted'">
                                <span class="text-green-500 font-medium">В друзьях</span>
                            </template>
                            <button
                                class="primary-btn !rounded-full !py-2 !px-4"
                                type="button"
                                @click="goToChat"
                            >
                                Перейти в чат
                            </button>
                        </template>
                    </div>
                </div>
            </header>

            <!-- Секция «Понравилось» — лайкнутые рилзы -->
            <section class="w-full px-4 pb-8">
                <h2 class="title mb-4">Понравилось</h2>
                <div v-if="likedSnippets.length === 0" class="bg-content rounded-2xl p-8 text-center context">
                    Пока нет понравившихся рилзов
                </div>
                <div
                    v-else
                    class="grid grid-cols-3 gap-1 sm:gap-2"
                >
                    <button
                        v-for="snippet in likedSnippets"
                        :key="snippet.id"
                        type="button"
                        class="aspect-[9/16] relative rounded-lg overflow-hidden bg-black/50 group"
                        @click="openReel(snippet.id)"
                    >
                        <img
                            v-if="snippet.track?.preview_url"
                            :src="snippet.track.preview_url"
                            :alt="snippet.track?.title"
                            class="absolute inset-0 w-full h-full object-cover"
                        />
                        <div v-else class="absolute inset-0 flex items-center justify-center">
                            <i class="fa-solid fa-music text-white/40 text-2xl"></i>
                        </div>
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/30 transition-colors" />
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="w-10 h-10 rounded-full bg-white/90 flex items-center justify-center">
                                <i class="fa-solid fa-play text-black text-sm"></i>
                            </span>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/80 to-transparent">
                            <p class="text-white text-xs font-medium truncate">
                                {{ snippet.track?.title || 'Без названия' }}
                            </p>
                        </div>
                    </button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
