<script setup>
const props = defineProps({
    src: {
        type: String,
        required: false,
    }
});
</script>

<template>
    <img :src="props.src" class="rounded-full w-8 h-8" alt="">
</template>