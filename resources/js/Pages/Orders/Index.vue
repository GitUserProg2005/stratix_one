<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
  orders: { type: Array, default: () => [] },
});
</script>

<template>
  <Head title="Заказы" />
  <AppLayout>
    <div class="p-4 space-y-3">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">История заказов</h1>
        <Link :href="route('map')" class="primary-btn">Карта</Link>
      </div>
      <div v-if="!orders.length" class="text-sm text-gray-500">Пока нет заказов.</div>
      <div v-for="order in orders" :key="order.id" class="border border-gray-200 rounded-lg p-3 bg-white">
        <div class="text-sm text-gray-700">#{{ order.id }} - {{ order.status }}</div>
        <div class="text-sm">A: {{ order.pickup_address || '-' }}</div>
        <div class="text-sm">B: {{ order.destination_address || '-' }}</div>
      </div>
    </div>
  </AppLayout>
</template>
