<script setup>
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Rectangle from '@/Components/Skeleton/Rectangle.vue';

const page = usePage();
const rates = ref([]);
const isLoading = ref(false);
const payingRateId = ref(null);
const errorMessage = ref('');

const authUser = computed(() => page.props.auth?.user ?? null);

function isCurrentRate(rate) {
    return authUser.value?.rate?.id === rate.id;
}

async function getRates() {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.get(route('get.rates'));

        if (response.data.result === 'ok') {
            rates.value = response.data.rates;
        }
    } catch (error) {
        console.error(error);
        errorMessage.value = 'Не удалось загрузить тарифы';
    } finally {
        isLoading.value = false;
    }
}

async function payRate(rate) {
    if (isCurrentRate(rate)) {
        return;
    }

    if (!authUser.value) {
        router.visit(route('login'));
        return;
    }

    payingRateId.value = rate.id;
    errorMessage.value = '';

    try {
        const response = await axios.post(route('payment.purchase'), {
            rate_id: rate.id,
            rate_title: rate.title,
            rate_price: rate.price,
        });

        if (response.data.payment_url) {
            window.location.href = response.data.payment_url;
            return;
        }

        errorMessage.value = response.data.error || response.data.context || 'Ошибка при создании платежа';
    } catch (error) {
        if (error.response?.status === 401) {
            router.visit(route('login'));
            return;
        }

        errorMessage.value = error.response?.data?.error
            || error.response?.data?.context
            || 'Ошибка при создании платежа';
    } finally {
        payingRateId.value = null;
    }
}

onMounted(() => {
    getRates();
});
</script>

<template>
    <div>
        <p v-if="errorMessage" class="badge badge-pending mb-4 w-full justify-center py-2">
            {{ errorMessage }}
        </p>

        <div v-if="isLoading" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <Rectangle
                v-for="i in 3"
                :key="i"
                height="16rem"
                rounded="rounded-2xl"
            />
        </div>

        <div v-else-if="!rates.length" class="content-outline p-6 text-center">
            <p class="context">Тарифы пока не добавлены</p>
        </div>

        <section v-else class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div
                v-for="rate in rates"
                :key="rate.id"
                class="content-outline flex flex-col p-5"
            >
                <div v-if="rate.picture" class="mb-3 aspect-video overflow-hidden rounded-xl">
                    <img
                        :src="rate.picture"
                        :alt="rate.title"
                        class="h-full w-full object-contain"
                    />
                </div>
                <div v-else class="mb-3 flex aspect-video items-center justify-center rounded-xl bg-content">
                    <i class="fa-solid fa-crown text-3xl text-gray-500" />
                </div>

                <h4 class="title-font-3 mb-1">{{ rate.title }}</h4>
                <p class="title-3 mb-3">{{ rate.price }} ₽</p>

                <ul v-if="rate.features?.length" class="context mb-4 flex-1 space-y-1">
                    <li v-for="(feature, index) in rate.features" :key="index">
                        <i class="fa-solid fa-check mr-2"></i> 
                        {{ typeof feature === 'object' && feature.name ? feature.name : feature }}
                    </li>
                </ul>

                <button
                    type="button"
                    class="primary-btn mt-auto flex w-full items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isCurrentRate(rate) || payingRateId === rate.id"
                    @click="payRate(rate)"
                >
                    <i v-if="payingRateId === rate.id" class="fa-solid fa-spinner fa-spin" />
                    <template v-else-if="isCurrentRate(rate)">Ваш текущий тариф</template>
                    <template v-else>
                        Оплатить
                        <i class="fa-solid fa-arrow-right text-xs" />
                    </template>
                </button>
            </div>
        </section>
    </div>
</template>
