<script setup>
import Modal from '@/Components/Modal.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import { ref } from 'vue';
import axios from 'axios';

const { workflowId } = defineProps({
    workflowId: {
        type: Number,
        required: true,
    },
});

const showModal = ref(false);
const title = ref('');
const description = ref('');
const categoryId = ref('');
const categories = ref([]);
const isSubmitting = ref(false);
const isLoading = ref(false);
const isPublished = ref(false);
const downloads = ref(0);

const categoryOptions = () =>
    categories.value.map((category) => ({
        label: category.title,
        value: category.id,
    }));

async function loadCategories() {
    if (categories.value.length) {
        return;
    }

    try {
        const response = await axios.get(route('catalog.categories'));
        if (response.data.result === 'ok') {
            categories.value = response.data.categories;
        }
    } catch (error) {
        console.error('Ошибка загрузки категорий:', error);
    }
}

async function loadPublishStatus() {
    isLoading.value = true;

    try {
        const response = await axios.get(route('catalog.publish-status', workflowId));

        if (response.data.result !== 'ok') {
            return;
        }

        isPublished.value = response.data.published;
        downloads.value = response.data.catalog_workflow?.downloads ?? 0;

        if (response.data.published && response.data.catalog_workflow) {
            const entry = response.data.catalog_workflow;
            title.value = entry.title ?? '';
            description.value = entry.description ?? '';
            categoryId.value = entry.category_id ?? '';
        } else {
            title.value = '';
            description.value = '';
            categoryId.value = '';
        }
    } catch (error) {
        console.error('Ошибка проверки статуса публикации:', error);
    } finally {
        isLoading.value = false;
    }
}

async function openModal() {
    showModal.value = true;
    await Promise.all([loadCategories(), loadPublishStatus()]);
}

function closeModal() {
    showModal.value = false;
}

async function deploy() {
    if (!title.value.trim() || !categoryId.value) {
        return;
    }

    isSubmitting.value = true;

    try {
        const response = await axios.post(route('catalog.deploy'), {
            workflow_id: workflowId,
            category_id: categoryId.value,
            title: title.value.trim(),
            description: description.value.trim() || null,
        });

        if (response.data.result === 'ok') {
            isPublished.value = true;
            downloads.value = response.data.catalogWorkflow?.downloads ?? downloads.value;
            closeModal();
        }
    } catch (error) {
        console.error('Ошибка публикации workflow:', error);
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <div class="relative">
        <button type="button" class="primary-btn-blur" aria-label="Опубликовать в каталог" @click="openModal">
            <i class="fa-solid fa-gear" />
        </button>

        <Modal :show="showModal" max-width="4xl" @close="closeModal">
            <div class="custom-scroll max-h-[90vh] space-y-4 overflow-y-auto p-4 md:p-6">
                <div class="flex flex-row items-center justify-between gap-3">
                    <h2 class="title-2">
                        {{ isPublished ? 'Каталог workflow' : 'Публикация в каталог' }}
                    </h2>
                    <button type="button" class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit" aria-label="Закрыть" @click="closeModal">
                        <i class="fa-solid fa-xmark text-xl" />
                    </button>
                </div>

                <div v-if="isLoading" class="context text-sm opacity-70">
                    Загрузка...
                </div>

                <div v-else class="grid grid-cols-1 gap-4">
                    <div
                        v-if="isPublished"
                        class="dashboard-inset flex items-center gap-3 rounded-xl p-3"
                    >
                        <i class="fa-solid fa-circle-check text-[var(--accent)]" />
                        <div>
                            <p class="dashboard-row-title text-sm">Уже опубликовано в каталоге</p>
                            <span class="label-accent mt-1 inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-download text-[10px]" />
                                {{ downloads }} установок
                            </span>
                        </div>
                    </div>

                    <div>
                        <h3 class="title-2">
                            Название
                            <span class="badge badge-pending">Обязательно</span>
                        </h3>
                        <input v-model="title" class="input mt-2 w-full" type="text" placeholder="Название workflow" />
                    </div>

                    <div>
                        <h3 class="title-2">
                            Категория
                            <span class="badge badge-pending">Обязательно</span>
                        </h3>
                        <HeadlessSelect
                            v-model="categoryId"
                            :options="categoryOptions()"
                            button-class="select-input mt-2 w-full"
                            placeholder="Выберите категорию"
                        />
                    </div>

                    <div>
                        <h3 class="title-2">Описание</h3>
                        <textarea
                            v-model="description"
                            class="input mt-2 w-full"
                            rows="4"
                            placeholder="Краткое описание workflow"
                        />
                    </div>

                    <div>
                        <button
                            type="button"
                            class="primary-btn flex items-center gap-2"
                            :disabled="isSubmitting || !title.trim() || !categoryId"
                            @click="deploy"
                        >
                            {{ isPublished ? 'Обновить в каталоге' : 'Опубликовать' }}
                            <i :class="isPublished ? 'fa-solid fa-rotate' : 'fa-solid fa-cloud-arrow-up'" />
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
