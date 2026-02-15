<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    user: Object
});
</script>

<template>
    <AppLayout>   
        <section class="w-full p-4">
            <div class="bg-content p-4 space-y-4">
                <h2 class="title">Profile content</h2>
                <pre>{{ user }}</pre>
                <button class="primary-btn">Добавить в друзья</button>
            </div>
        </section>
    </AppLayout>
</template>