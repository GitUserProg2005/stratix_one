<script setup>
const emit = defineEmits(['back']);

defineProps({
    /** Предыдущий URL (route) для возврата. Если не передан — переход на главную. */
    backUrl: {
        type: String,
        default: null
    }
});

function goBack() {
    emit('back');
}
</script>

<template>
    <button type="button" @click="goBack" aria-label="Назад">
        <i class="fa-solid fa-arrow-left"></i>
    </button>
</template>
