<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Search from '@/Components/Search/Search.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import Avatar from '@/Components/Avatar.vue';
import { useProjectMembers } from '@/composables/useProjectMembers';

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);

const {
    addedUsers,
    searchRef,
    searchUsers,
    addUser,
    removeUser,
    setMembers,
    clearMembers,
} = useProjectMembers();

const showModal = ref(false);
const loading = ref(false);
const error = ref('');
const title = ref('');
const status = ref('');
const ownerUserId = ref(null);
const projectId = ref(null);
const project = ref(null);
const members = ref([]);

const isAllowedToEdit = computed(() => {
    if (!project.value || !currentUserId.value) return false;

    return (project.value.users ?? []).some(
        (u) => u.id === currentUserId.value && u.is_owner,
    );
});

const memberOptions = computed(() => {
    return members.value.map((user) => ({
        value: user.id,
        label: user.name,
    }));
});

const statusOptions = [
    { value: 'started', label: 'Начатый' },
    { value: 'in_progress', label: 'В процессе' },
    { value: 'completed', label: 'Завершён' },
];

function openModal(projectData, projectMembers = []) {
    if (!projectData?.id) return;

    // 1. Подгружаем данные проекта в форму
    project.value = projectData;
    projectId.value = projectData.id;
    title.value = projectData.title ?? '';
    status.value = typeof projectData.status === 'object'
        ? (projectData.status?.value ?? 'started')
        : (projectData.status ?? 'started');
    members.value = projectMembers;
    ownerUserId.value = null;
    setMembers(projectMembers);
    error.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function resetForm() {
    project.value = null;
    projectId.value = null;
    title.value = '';
    status.value = '';
    ownerUserId.value = null;
    members.value = [];
    clearMembers();
    error.value = '';
}

async function submit() {
    if (!isAllowedToEdit.value) return;

    error.value = '';

    if (!projectId.value) {
        error.value = 'Проект не выбран';
        return;
    }

    if (!title.value.trim()) {
        error.value = 'Укажите название проекта';
        return;
    }

    if (!status.value) {
        error.value = 'Укажите статус проекта';
        return;
    }

    loading.value = true;

    try {
        // 1. Сначала передаём владение, пока выбранный участник ещё в проекте
        if (ownerUserId.value) {
            await axios.put(route('projects.assign-owner', projectId.value), {
                user_id: ownerUserId.value,
            });
        }

        // 2. Собираем FormData
        const formData = new FormData();
        formData.append('title', title.value.trim());
        formData.append('status', status.value);
        formData.append('_method', 'PUT');

        addedUsers.value
            .filter((user) => user.id !== ownerUserId.value)
            .forEach((user) => {
                formData.append('member_ids[]', String(user.id));
            });

        // 3. Обновляем проект
        const { data } = await axios.post(route('projects.update', projectId.value), formData);

        if (data.result === 'ok') {
            resetForm();
            closeModal();
            router.reload({ only: ['projects'] });
        }
    } catch (e) {
        error.value = e.response?.data?.message
            || e.response?.data?.errors?.title?.[0]
            || e.response?.data?.errors?.status?.[0]
            || 'Не удалось обновить проект';
    } finally {
        loading.value = false;
    }
}

defineExpose({ openModal, closeModal });
</script>

<template>
    <Modal :show="showModal" max-width="lg" @close="closeModal">
        <div class="space-y-4 p-4 md:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="title-2">Изменить проект</h2>
                <button
                    type="button"
                    class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit"
                    aria-label="Закрыть"
                    @click="closeModal"
                >
                    <i class="fa-solid fa-xmark text-xl" />
                </button>
            </div>

            <p
                v-if="!isAllowedToEdit"
                class="ai-thought-broken rounded-xl px-3 py-2 text-sm"
            >
                Только владелец может редактировать данные проекта
            </p>

            <fieldset
                :disabled="!isAllowedToEdit"
                class="m-0 space-y-4 border-0 p-0"
                :class="{ 'opacity-70': !isAllowedToEdit }"
            >
                <div>
                    <h3 class="title-3">Название</h3>
                    <input
                        v-model="title"
                        type="text"
                        class="input mt-2 w-full"
                        placeholder="Название проекта"
                    />
                </div>

                <div>
                    <h3 class="title-3">Статус</h3>
                    <HeadlessSelect
                        v-model="status"
                        class="mt-2"
                        :options="statusOptions"
                        placeholder="Выберите статус"
                    />
                </div>

                <div class="my-2">
                    <h3 class="title-3">Назначить владельцем</h3>
                    <p class="text-red-500 text-sm">Владелец проекта будет иметь доступ к всем задачам и проектам.</p>

                    <HeadlessSelect
                        v-model="ownerUserId"
                        class="mt-2"
                        :options="memberOptions"
                        placeholder="Выберите участника"
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

                <div class="flex justify-start gap-2 pt-2">
                    <button type="button" class="primary-btn" :disabled="loading" @click="submit">
                        {{ loading ? 'Сохранение…' : 'Сохранить' }}
                    </button>
                </div>
            </fieldset>
        </div>
    </Modal>
</template>
