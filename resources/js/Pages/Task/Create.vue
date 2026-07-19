<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';

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
        <button type="button" class="primary-btn" @click="openModal">
            Создать задачу
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
                        @update:model-value="parentId = null"
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
