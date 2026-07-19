<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import Search from '@/Components/Search/Search.vue';
import Avatar from '@/Components/Avatar.vue';
import { useProjectMembers } from '@/composables/useProjectMembers';

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
    tasks: {
        type: Array,
        default: () => [],
    },
});

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
const projectId = ref(null);
const parentId = ref(null);
const status = ref('started');
const difficulty = ref('normal');
const dueAt = ref('');

const projectOptions = computed(() =>
    props.projects.map((p) => ({ value: p.id, label: p.title })),
);

const statusOptions = [
    { value: 'started', label: 'К выполнению' },
    { value: 'in_progress', label: 'В работе' },
    { value: 'review', label: 'На проверке' },
    { value: 'completed', label: 'Готово' },
];

const difficultyOptions = [
    { value: 'easy', label: 'Easy' },
    { value: 'normal', label: 'Normal' },
    { value: 'hard', label: 'Hard' },
];

// Родители только depth < 2 (ребёнок станет ≤ 3 уровнем)
const parentOptions = computed(() => {
    const options = [{ value: null, label: 'Без родителя (корень)' }];

    if (!projectId.value) {
        return options;
    }

    props.tasks
        .filter((t) => Number(t.project_id) === Number(projectId.value) && Number(t.depth ?? 0) < 2)
        .forEach((t) => {
            options.push({ value: t.id, label: t.title });
        });

    return options;
});

function searchProjectUsers(query) {
    return searchUsers(query, projectId.value);
}

function onProjectChange() {
    parentId.value = null;
    clearMembers();
}

function openModal() {
    error.value = '';
    showModal.value = true;

    if (!projectId.value && props.projects.length === 1) {
        projectId.value = props.projects[0].id;
    }
}

function closeModal() {
    showModal.value = false;
}

function resetForm() {
    title.value = '';
    parentId.value = null;
    status.value = 'started';
    difficulty.value = 'normal';
    dueAt.value = '';
    clearMembers();
    error.value = '';
}

async function submit() {
    error.value = '';

    if (!title.value.trim()) {
        error.value = 'Укажите название';
        return;
    }

    if (!projectId.value) {
        error.value = 'Выберите проект';
        return;
    }

    loading.value = true;

    try {
        // 1. Собираем FormData
        const formData = new FormData();
        formData.append('title', title.value.trim());
        formData.append('project_id', String(projectId.value));
        formData.append('status', status.value);
        formData.append('difficulty', difficulty.value);

        if (parentId.value) {
            formData.append('parent_id', String(parentId.value));
        }

        if (dueAt.value) {
            formData.append('due_at', dueAt.value);
        }

        addedUsers.value.forEach((user) => {
            formData.append('worker_ids[]', String(user.id));
        });

        // 2. Шлём на бэкенд
        const { data } = await axios.post(route('tasks.store'), formData);

        if (data.result === 'ok') {
            resetForm();
            closeModal();
            router.reload({ only: ['tasks', 'projects'] });
        }
    } catch (e) {
        error.value = e.response?.data?.message
            || e.response?.data?.errors?.title?.[0]
            || 'Не удалось создать задачу';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div>
        <button type="button" class="dashboard-icon-slot transition hover:bg-[color-mix(in_srgb,#ef4444_18%,transparent)] disabled:opacity-50" @click="openModal">
            <i class="fa-solid fa-plus text-[var(--accent)]"></i>
        </button>

        <Modal :show="showModal" max-width="lg" @close="closeModal">
            <div class="space-y-4 p-4 md:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="title-2">Новая задача</h2>
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
                        class="input mt-2 w-full"
                        type="text"
                        placeholder="Название задачи"
                    />
                </div>

                <div>
                    <h3 class="title-3">Проект</h3>
                    <HeadlessSelect
                        v-model="projectId"
                        class="mt-2"
                        :options="projectOptions"
                        placeholder="Выберите проект"
                        @update:model-value="onProjectChange"
                    />
                </div>

                <div>
                    <h3 class="title-3">Родительская задача</h3>
                    <HeadlessSelect
                        v-model="parentId"
                        class="mt-2"
                        :options="parentOptions"
                        placeholder="Без родителя"
                    />
                    <p class="context mt-1">Вложенность максимум 3 уровня</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <h3 class="title-3">Статус</h3>
                        <HeadlessSelect
                            v-model="status"
                            class="mt-2"
                            :options="statusOptions"
                        />
                    </div>
                    <div>
                        <h3 class="title-3">Сложность</h3>
                        <HeadlessSelect
                            v-model="difficulty"
                            class="mt-2"
                            :options="difficultyOptions"
                        />
                    </div>
                </div>

                <div>
                    <h3 class="title-3">Дедлайн</h3>
                    <input
                        v-model="dueAt"
                        class="input mt-2 w-full"
                        type="date"
                    />
                </div>

                <div class="dashboard-inset">
                    <h3 class="title-3">Исполнители</h3>
                    <p v-if="!projectId" class="context mt-2">Сначала выберите проект</p>

                    <Search
                        v-else
                        ref="searchRef"
                        :search-fn="searchProjectUsers"
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
