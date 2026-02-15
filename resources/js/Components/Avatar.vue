<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    name: String,
    src: String,
    userId: Number
})

const initial = computed(() => props.name ? props.name[0] : '')
const imageError = ref(false)

function handleImageError() {
    imageError.value = true;
}
</script>

<template>
    <Link :href="route('user.profile', userId)">
        <span 
            v-if="!props.src || imageError"
            class="w-8 h-8 bg-orange-400 rounded-full text-black flex items-center font-bold justify-center"
        >
            {{ initial }}
        </span>
        <img
            v-else
            :src="props.src"
            :alt="props.name"
            class="w-8 h-8 rounded-full object-cover"
            @error="handleImageError"
            @load="imageError = false"
        />
    </Link>
</template>
