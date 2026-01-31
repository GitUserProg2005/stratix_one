<script setup>
import { computed } from 'vue'

const props = defineProps({
    name: String,
})

const initial = computed(() => props.name ? props.name[0] : '')
</script>

<template>
    <div class="p-1 bg-content rounded-full">
        <span class="w-10 h-10 bg-orange-400 rounded-full text-black flex items-center font-bold justify-center">
            {{ initial }}
        </span>
    </div>
</template>
