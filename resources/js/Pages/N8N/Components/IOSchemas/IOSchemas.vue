<script setup>
import Modal from '@/Components/Modal.vue';
import SchemaTree from './SchemaTree.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    schemas: Object,
    nodeType: String,
});

const nodeSchema = computed(() => {
    return props.schemas?.[props.nodeType] || null;
});

const isOpenModal = ref(false);

function toggleModal() {
    isOpenModal.value = !isOpenModal.value;
}
</script> 

<template>
    <button @click="toggleModal">
        <i class="fa-brands fa-hashnode"></i>
    </button>

    <Modal :show="isOpenModal" @close="toggleModal">
        <div class="custom-scroll max-h-[90vh] space-y-6 overflow-y-auto p-4 md:p-6">
            
            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <h2 class="title-2">
                    Входные-выходные данные
                </h2>

                <button
                    type="button"
                    class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit"
                    aria-label="Закрыть"
                    @click="toggleModal"
                >
                    <i class="fa-solid fa-xmark text-xl" />
                </button>
            </div>

            <!-- Loading -->
            <div v-if="!nodeSchema">
                Загрузка схемы...
            </div>

            <template v-else>

                <!-- INPUT -->
                <section class="space-y-3">
                    <h3 class="t-body">
                        Входные данные
                    </h3>

                    <div
                        v-if="!Object.keys(nodeSchema.inputSchema || {}).length"
                        class="opacity-70"
                    >
                        Нет входных данных
                    </div>

                    <div
                        v-else
                        class="flex flex-wrap items-center gap-2"
                    >
                        <div v-if="nodeSchema.inputSchema">
                            <SchemaTree
                                :schema="nodeSchema.inputSchema"
                            />
                        </div>
                    </div>
                </section>

                <!-- OUTPUT -->
                <section class="space-y-3">
                    <h3 class="t-body">
                        Выходные данные
                    </h3>

                    <div
                        v-if="!Object.keys(nodeSchema.outputSchema || {}).length"
                        class="opacity-70"
                    >
                        Нет выходных данных
                    </div>

                    <div
                        v-else
                        class="flex flex-wrap items-center gap-2"
                    >
                        <div v-if="nodeSchema.outputSchema">
                            <SchemaTree
                                :schema="nodeSchema.outputSchema"
                            />
                        </div>
                    </div>
                </section>
            </template>
        </div>
    </Modal>
</template>