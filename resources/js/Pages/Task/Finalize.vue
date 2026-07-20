<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';

const showModal = ref(false);
const loading = ref(false);
const error = ref('');
const reason = ref('');
const task = ref(null);

const emit = defineEmits(['finalized']);

function openModal(taskData) {
    task.value = taskData;
    reason.value = '';
    error.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    task.value = null;
    reason.value = '';
    error.value = '';
}

async function confirmFinalize() {
    if (!task.value?.id || loading.value) return;

    loading.value = true;
    error.value = '';

    try {
        // Финализируем задачу и триггерим связанные workflow
        const response = await axios.post(route('tasks.finalize', task.value.id), {
            reason: reason.value || null,
        });

        if (response.data.result === 'ok') {
            emit('finalized', response.data.task);
            closeModal();
        }
    } catch (e) {
        error.value = e.response?.data?.message || 'Не удалось подтвердить задачу';
    } finally {
        loading.value = false;
    }
}

defineExpose({ openModal });
</script>

<template>
    <Modal :show="showModal" max-width="lg" @close="closeModal">
        <div class="space-y-4 p-4 md:p-6">
            <div class="flex flex-row items-center justify-between gap-3">
                <h2 class="title-2">Подтвердить выполнение</h2>
                <button
                    type="button"
                    class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit"
                    aria-label="Закрыть"
                    @click="closeModal"
                >
                    <i class="fa-solid fa-xmark text-xl" />
                </button>
            </div>

            <p class="context" v-if="task">
                Задача «{{ task.title }}» будет финализирована. При наличии автоматизаций —
                запустится связанный workflow.
            </p>

            <div>
                <h3 class="title-2 text-sm">
                    Причина
                    <span class="badge badge-neutral">Необязательно</span>
                </h3>
                <textarea
                    v-model="reason"
                    class="input mt-2 w-full min-h-[6rem]"
                    placeholder="Почему задача закрыта / что сделано"
                />
            </div>

            <p v-if="error" class="context text-red-400">{{ error }}</p>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    class="primary-btn flex items-center gap-2"
                    :disabled="loading"
                    @click="confirmFinalize"
                >
                    Подтвердить
                    <i class="fa-solid fa-check" />
                </button>

                <button
                    type="button"
                    class="badge badge-neutral cursor-pointer"
                    :disabled="loading"
                    @click="closeModal"
                >
                    Отмена
                </button>
            </div>
        </div>
    </Modal>
</template>
