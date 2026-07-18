<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import SecretApiKey from '@/Components/SecretApiKey.vue';

const emit = defineEmits(['open']);

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const isOpenModal = ref(false);

function openModal() {
    isOpenModal.value = true;
    emit('open');
}

function closeModal() {
    isOpenModal.value = false;
}
</script>

<template>
    <div>
        <button
            type="button"
            class="sidebar-nav-link-nested w-full text-left"
            @click="openModal"
        >
            <i class="fa-solid fa-gear text-sm"></i>
            Настройки
        </button>

        <Modal :show="isOpenModal" max-width="lg" @close="closeModal">
            <div class="custom-scroll max-h-[90vh] space-y-4 overflow-y-auto p-4 md:p-6">
                <div class="flex flex-row items-center justify-between gap-3">
                    <h2 class="title-2">Настройки</h2>
                    <button
                        type="button"
                        class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit"
                        aria-label="Закрыть"
                        @click="closeModal"
                    >
                        <i class="fa-solid fa-xmark text-xl" />
                    </button>
                </div>

                <section class="dashboard-inset space-y-2 rounded-xl p-4">
                    <h3 class="title-2 text-sm"><i class="fa-solid fa-key text-sm mr-2"></i> API-ключ</h3>
                    <p class="context text-xs opacity-70">
                        Используйте ключ в заголовке запросов к вебхукам
                    </p>
                    
                    <div class="mt-6"> 
                    <SecretApiKey :prefix="user?.prefix_api_key" />
                    </div>
                </section>
            </div>
        </Modal>
    </div>
</template>
