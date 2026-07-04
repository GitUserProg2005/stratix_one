<script setup>
import { ref } from 'vue';
import ModalPricingRate from '@/Components/PricingRate/ModalPricingRate.vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
});

const pricingModalRef = ref(null);
</script>

<template>
    <div class="content-outline rounded-2xl p-6">
        <h2 class="title-2 mb-3">{{ title }}</h2>

        <img src="/img/maskot-sad.png" class="object-contain w-64 scale-x-[-1] mb-3" alt="">

        <p class="context">
            Чтобы получить доступ, можно приобрести
            <button
                type="button"
                class="border-0 bg-transparent p-0 font-bold text-[var(--accent)] underline-offset-2 hover:underline"
                @click="pricingModalRef?.openModal()"
            >
                тарифный план
            </button>
        </p>

        <ModalPricingRate ref="pricingModalRef" hide-trigger />
    </div>
</template>
