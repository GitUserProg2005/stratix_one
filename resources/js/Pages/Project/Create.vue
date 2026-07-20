<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Search from '@/Components/Search/Search.vue';
import Avatar from '@/Components/Avatar.vue';
import { useProjectMembers } from '@/composables/useProjectMembers';

const {
    addedUsers,
    searchRef,
    searchUsers,
    addUser,
    removeUser,
    clearMembers,
} = useProjectMembers();

const showModal = ref(false);
const loading = ref(false);
const error = ref('');
const title = ref('');

function openModal() {
    error.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function resetForm() {
    title.value = '';
    clearMembers();
    error.value = '';
}

async function submit() {
    error.value = '';

    if (!title.value.trim()) {
        error.value = 'Укажите название проекта';
        return;
    }

    loading.value = true;

    try {
        // 1. Собираем FormData
        const formData = new FormData();
        formData.append('title', title.value.trim());

        addedUsers.value.forEach((user) => {
            formData.append('member_ids[]', String(user.id));
        });

        // 2. Создаём проект
        const { data } = await axios.post(route('projects.store'), formData);

        if (data.result === 'ok') {
            resetForm();
            closeModal();
            router.reload({ only: ['projects'] });
        }
    } catch (e) {
        error.value = e.response?.data?.message
            || e.response?.data?.errors?.title?.[0]
            || 'Не удалось создать проект';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div>
        <button type="button" class="primary-btn" @click="openModal">
            <i class="fa-solid fa-plus" />
            Добавить проект
        </button>

        <Modal :show="showModal" max-width="lg" @close="closeModal">
            <div class="space-y-4 p-4 md:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="title-2">Создание проекта</h2>
                    <button
                        type="button"
                        class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit"
                        aria-label="Закрыть"
                        @click="closeModal"
                    >
                        <i class="fa-solid fa-xmark text-xl" />
                    </button>
                </div>

                <div>
                    <h3 class="title-3">Название</h3>
                    <input
                        v-model="title"
                        type="text"
                        class="input mt-2 w-full"
                        placeholder="Название проекта"
                    />
                </div>

                <div class="dashboard-inset">
                    <h3 class="title-3">Участники</h3>

                    <Search
                        ref="searchRef"
                        :search-fn="searchUsers"
                        search-label="Имя или email"
                        class="mt-4"
                    >
                        <template #item="{ item }">
                            <div class="flex items-center gap-3">
                                <Avatar
                                    :name="item.name"
                                    :src="item.avatar_url"
                                    :user-id="item.id"
                                    :no-link="true"
                                    size="sm"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="title-3 truncate">{{ item.name }}</div>
                                    <p class="t-mini truncate">{{ item.email }}</p>
                                </div>
                                <button type="button" class="dashboard-icon-slot" @click="addUser(item)">
                                    <i class="fa-solid fa-plus" />
                                </button>
                            </div>
                        </template>
                    </Search>

                    <div v-if="addedUsers.length" class="space-y-2 mt-4">
                        <div
                            v-for="user in addedUsers"
                            :key="user.id"
                            class="flex items-center gap-3"
                        >
                            <Avatar
                                :name="user.name"
                                :src="user.avatar_url"
                                :user-id="user.id"
                                :no-link="true"
                                size="sm"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="title-3 truncate">{{ user.name }}</div>
                                <p class="t-mini truncate">{{ user.email }}</p>
                            </div>
                            <button type="button" class="dashboard-icon-slot" @click="removeUser(user)">
                                <i class="fa-solid fa-xmark" />
                            </button>
                        </div>
                    </div>
                </div>

                <p v-if="error" class="context text-[var(--accent)]">{{ error }}</p>

                <div class="flex justify-start gap-2 pt-2">
                    <button type="button" class="primary-btn" :disabled="loading" @click="submit">
                        {{ loading ? 'Создание…' : 'Создать' }}
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
