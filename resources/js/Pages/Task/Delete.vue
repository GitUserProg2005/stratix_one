<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Confirm from '@/Components/Confirm.vue';

const props = defineProps({
    taskId: {
        type: [Number, String],
        required: true,
    },
});

const emit = defineEmits(['deleted']);

const confirmRef = ref(null);
const loading = ref(false);

function openConfirm() {
    confirmRef.value?.open();
}

function destroy() {
    if (!props.taskId) return;

    loading.value = true;

    return new Promise((resolve, reject) => {
        router.delete(route('tasks.delete', props.taskId), {
            onSuccess: () => {
                emit('deleted');
                resolve();
            },
            onError: reject,
            onFinish: () => {
                loading.value = false;
            },
        });
    });
}
</script>

<template>
    <div>
        <button
            type="button"
            class="dashboard-icon-slot transition hover:bg-[color-mix(in_srgb,#ef4444_18%,transparent)] disabled:opacity-50"
            title="Удалить"
            :disabled="loading"
            @click="openConfirm"
        >
            <i class="fa-solid fa-trash text-red-400" />
        </button>

        <Confirm
            ref="confirmRef"
            message="Удалить задачу? Дочерние задачи тоже будут удалены."
            :on-confirm="destroy"
        />
    </div>
</template>
