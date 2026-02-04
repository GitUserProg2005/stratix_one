<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    /** Предыдущий URL (route) для возврата. Если не передан — переход на главную. */
    backUrl: {
        type: String,
        default: null
    }
});

function goBack() {
    if (props.backUrl) {
        router.visit(props.backUrl);
    } else {
        router.get(route('tracks.index'));
    }
}
</script>

<template>
    <button type="button" @click="goBack" aria-label="Назад">
        <i class="fa-solid fa-arrow-left"></i>
    </button>
</template>
