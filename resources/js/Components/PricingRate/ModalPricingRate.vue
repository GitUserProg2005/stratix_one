<script setup>
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import PricingRate from './PricingRate.vue';

defineProps({
    buttonLabel: {
        type: String,
        default: 'Тарифы',
    },
});

const showModal = ref(false);

function openModal() {
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}
</script>

<template>
    <div class="inline-flex">
        <button type="button" class="tag t-mini" @click.stop="openModal">
            {{ buttonLabel }}
        </button>

        <Modal :show="showModal" max-width="5xl" @close="closeModal">
            <div class="custom-scroll max-h-[90vh] space-y-4 overflow-y-auto p-4 md:p-6">
                <div class="flex flex-row items-center justify-between gap-3">
                    <h2 class="title-2">Тарифы</h2>
                    <button type="button" class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit" aria-label="Закрыть" @click="closeModal">
                        <i class="fa-solid fa-xmark text-xl" />
                    </button>
                </div>

                <PricingRate />
            </div>
        </Modal>
    </div>
</template>
