<script setup>
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    message: {
        type: String,
        required: true,
    },
    onConfirm: {
        type: Function,
        required: true,
    },
});

const isOpen = ref(false);
const loading = ref(false);

function open() {
    isOpen.value = true;
}

function close() {
    if (loading.value) return;
    isOpen.value = false;
}

async function confirm() {
    if (loading.value) return;

    loading.value = true;

    try {
        await props.onConfirm();
        isOpen.value = false;
    } finally {
        loading.value = false;
    }
}

defineExpose({ open, close });
</script>

<template>
    <Modal :show="isOpen" max-width="sm" @close="close">
        <div class="space-y-6 p-4 md:p-6">
            <p class="t-color-danger text-sm leading-relaxed">
                {{ message }}
            </p>

            <div class="flex items-center justify-end gap-2">
                <button
                    type="button"
                    class="px-4 !w-auto transition disabled:opacity-50"
                    :disabled="loading"
                    @click="close"
                >
                    Отмена
                </button>
                <button
                    type="button"
                    class="primary-btn !bg-[#ef4444] hover:!opacity-90"
                    :disabled="loading"
                    @click="confirm"
                >
                    {{ loading ? 'Удаление…' : 'Удалить' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
