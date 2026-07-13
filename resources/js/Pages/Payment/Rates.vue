<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
defineProps({
  rates: {
    type: Array,
    default: () => [],
  },
});

const authUser = computed(() => page.props.auth?.user ?? null);
const errorMessage = ref('');

function payRate(rate) {
  if (!authUser.value) {
    router.visit(route('login'));
    return;
  }

  errorMessage.value = '';
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  
  fetch(route('payment.purchase'), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({
      _token: csrfToken,
      rate_id: rate.id,
      rate_title: rate.title,
      rate_price: rate.price,
    }),
    credentials: 'same-origin',
  })
    .then((res) => res.json().then((data) => ({ status: res.status, data })))
    .then(({ status, data }) => {
      if (status === 401) {
        router.visit(route('login'));
        return;
      }
      if (status === 200 && data.payment_url) {
        window.location.href = data.payment_url;
      } else {
        errorMessage.value = data.error || data.context || 'Ошибка при создании платежа';
      }
    })
    .catch(() => {
      errorMessage.value = 'Ошибка при создании платежа';
    });
}
</script>

<template>
  <AppLayout>
    <div class="p-4 pb-24 max-w-4xl mx-auto">
      <h1 class="text-2xl font-bold tracking-tight mb-2">Тарифы и подписка</h1>
      <p class="text-gray-400 text-sm mb-8">
        Тестовый интерфейс оплаты YooKassa. Выберите тариф и нажмите «Оплатить» — будет создана ссылка на оплату.
      </p>

      <div v-if="page.props.flash?.error || errorMessage" class="mb-4 p-3 rounded-xl bg-red-500/20 text-red-300 text-sm">
        {{ page.props.flash?.error || errorMessage }}
      </div>

      <div v-if="!rates.length" class="content p-6 text-center text-gray-400">
        Тарифы пока не добавлены. Создайте их в
        <a :href="'/admin'" class="text-[#15c1c1] hover:underline">админ-панели MoonShine</a>
        (раздел Rates).
      </div>

      <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="rate in rates"
          :key="rate.id"
          class="content p-5 rounded-2xl flex flex-col"
        >
          <div v-if="rate.picture" class="mb-3 rounded-xl overflow-hidden aspect-video bg-content-glass">
            <img
              :src="rate.picture"
              :alt="rate.title"
              class="w-full h-full object-contain"
            />
          </div>
          <div v-else class="mb-3 rounded-xl aspect-video bg-[#2C2C2C] flex items-center justify-center">
            <i class="fa-solid fa-crown text-3xl text-gray-500" />
          </div>
          <h2 class="title text-lg mb-1">{{ rate.title }}</h2>
          <p class="text-2xl font-bold text-white mb-3">{{ rate.price }} ₽</p>
          <ul v-if="rate.features && rate.features.length" class="text-sm text-gray-400 space-y-1 mb-4 flex-1">
            <li v-for="(f, i) in rate.features" :key="i">
              {{ typeof f === 'object' && f.name ? f.name : f }}
            </li>
          </ul>
          <button
            type="button"
            class="primary-btn w-full inline-flex items-center justify-center gap-2 mt-auto"
            @click="payRate(rate)"
          >
            Оплатить
            <i class="fa-solid fa-arrow-right text-xs" />
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
