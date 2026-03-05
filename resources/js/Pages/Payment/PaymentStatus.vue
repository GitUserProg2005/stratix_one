<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  user: { type: Object, default: null },
  purchace: { type: Object, required: true },
  rate: { type: Object, default: null },
});
</script>

<template>
  <AppLayout>
    <div class="p-4 pb-24 max-w-lg mx-auto">
      <h1 class="text-2xl font-bold tracking-tight mb-6">Статус оплаты</h1>

      <div class="content p-6 space-y-4">
        <div class="flex items-center gap-3">
          <div
            class="w-12 h-12 rounded-full flex items-center justify-center"
            :class="purchace.is_paid ? 'bg-green-500/20 text-green-400' : 'bg-amber-500/20 text-amber-400'"
          >
            <i
              :class="purchace.is_paid ? 'fa-solid fa-check' : 'fa-solid fa-clock'"
              class="text-xl"
            />
          </div>
          <div>
            <p class="font-semibold">
              {{ purchace.is_paid ? 'Оплачено' : 'Ожидание оплаты' }}
            </p>
            <p class="text-sm text-gray-400">
              Платёж {{ purchace.payment_id ? `#${String(purchace.payment_id).slice(0, 8)}…` : '—' }}
            </p>
          </div>
        </div>

        <template v-if="purchace.is_paid && rate">
          <p class="text-gray-400 text-sm">Активирован тариф:</p>
          <p class="title">{{ rate.title }}</p>
          <p class="text-gray-400 text-sm">{{ rate.price }} ₽</p>
        </template>

        <div class="pt-4 border-t border-white/10">
          <Link
            :href="route('rates')"
            class="primary-btn inline-flex items-center gap-2"
          >
            <i class="fa-solid fa-crown text-xs" />
            К тарифам
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
