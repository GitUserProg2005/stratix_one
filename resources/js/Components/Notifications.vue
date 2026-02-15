<script setup>
import { ref, watch, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import Avatar from '@/Components/Avatar.vue';

const open = ref(false);
const requests = ref([]);
const loading = ref(false);

async function fetchRequests() {
    loading.value = true;
    try {
        const { data } = await axios.get(route('friends.requests'));
        requests.value = data;
    } catch {
        requests.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetchRequests());
watch(open, (isOpen) => {
    if (isOpen) fetchRequests();
});

async function accept(friendshipId) {
    try {
        await axios.post(route('friends.accept', friendshipId));
        requests.value = requests.value.filter((r) => r.id !== friendshipId);
    } catch {
        alert('Ошибка принятия заявки');
    }
}

async function reject(friendshipId) {
    try {
        await axios.post(route('friends.reject', friendshipId));
        requests.value = requests.value.filter((r) => r.id !== friendshipId);
    } catch {
        alert('Ошибка отклонения заявки');
    }
}
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="p-1 rounded-full hover:bg-white/10 transition-colors relative"
            aria-label="Заявки в друзья"
            @click="open = !open"
        >
            <i class="fa-regular fa-bell text-lg"></i>
            <span
                v-if="open === false && requests.length > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-red-500 text-white text-xs"
            >
                {{ requests.length > 99 ? '99+' : requests.length }}
            </span>
        </button>

        <!-- Overlay -->
        <div
            v-show="open"
            class="fixed inset-0 z-40"
            aria-hidden="true"
            @click="open = false"
        />

        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute z-50 right-0 mt-2 w-80 max-h-[70vh] flex flex-col rounded-2xl bg-content overflow-hidden"
            >
                <div class="p-3 border-b border-gray-700">
                    <h3 class="font-semibold">Заявки в друзья</h3>
                </div>
                <div class="overflow-y-auto flex-1 min-h-0">
                    <div v-if="loading" class="p-4 text-center text-gray-400">
                        Загрузка...
                    </div>
                    <div v-else-if="requests.length === 0" class="p-4 text-center text-gray-400 text-sm">
                        Нет новых заявок
                    </div>
                    <ul v-else class="divide-y divide-gray-700">
                        <li
                            v-for="req in requests"
                            :key="req.id"
                            class="p-3 flex items-center gap-3 hover:bg-white/5"
                        >
                            <Link
                                :href="route('user.profile', req.sender.id)"
                                class="flex items-center gap-3 min-w-0 flex-1"
                                @click="open = false"
                            >
                                <Avatar
                                    :name="req.sender?.name"
                                    :src="req.sender?.avatar_url"
                                    :userId="req.sender?.id"
                                />
                                <span class="truncate font-medium">{{ req.sender?.name }}</span>
                            </Link>
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="btn-positive !py-1.5 !px-2.5"
                                    title="Принять"
                                    @click="accept(req.id)"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn-negative !py-1.5 !px-2.5"
                                    title="Отклонить"
                                    @click="reject(req.id)"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </Transition>
    </div>
</template>
