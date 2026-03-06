<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="p-4 lg:p-6">
            <h1 class="title mb-4">Dashboard</h1>
            <p class="context mb-4">Шаблон приложения: авторизация, оплата (YooKassa), WebSocket (Echo/Reverb).</p>
            <a :href="route('counter')" class="primary-btn inline-flex items-center gap-2">
                Тест WebSocket — счётчик
                <i class="fa-solid fa-arrow-right text-xs" />
            </a>
        </div>
    </AppLayout>
</template>
