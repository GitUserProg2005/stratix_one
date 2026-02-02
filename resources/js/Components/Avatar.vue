<script setup>
import { computed } from 'vue'

const props = defineProps({
    name: String,
})

const initial = computed(() => props.name ? props.name[0] : '')
</script>

<template>
    <span class="w-8 h-8 bg-orange-400 rounded-full text-black flex items-center font-bold justify-center">
        {{ initial }}
    </span>
</template>
