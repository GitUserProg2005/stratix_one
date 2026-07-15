<script setup>
import Modal from '@/Components/Modal.vue';
import SchemaTree from './SchemaTree.vue';
import { resolveDynamicSchema } from '@/Pages/N8N/utils/resolveDynamicSchema';

import Rectangle from '@/Components/Skeleton/Rectangle.vue';
import Text from '@/Components/Skeleton/Text.vue';

import { ref, computed } from 'vue';

const props = defineProps({
    schemas: Object,
    node: Object,
    nodeType: String,
    schemasLoading: {
        type: Boolean,
        default: false,
    },
});

// Resolve + взятие input/output схем конкретной ноды
const nodeSchema = computed(() => {
    const typeSchema = props.schemas?.[props.nodeType];
    if (!typeSchema) {
        return null;
    }

    return {
        inputSchema: typeSchema.dynamic_input
            ? resolveDynamicSchema(props.node, 'input', typeSchema)
            : typeSchema.inputSchema,
        outputSchema: typeSchema.dynamic_output
            ? resolveDynamicSchema(props.node, 'output', typeSchema)
            : typeSchema.outputSchema,
    };
});

const hasSchemaTree = (schema) => {
    if (!schema) return false;
    if (Array.isArray(schema)) return schema.length > 0;
    return typeof schema === 'object' && Object.keys(schema).length > 0;
};

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
            <div v-if="schemasLoading" class="space-y-6" aria-busy="true" aria-label="Загрузка схемы">
                <section class="space-y-3">
                    <Rectangle height="1rem" width="8rem" rounded="rounded-md" />
                    <div class="flex flex-wrap gap-2">
                        <Rectangle
                            v-for="i in 4"
                            :key="`in-${i}`"
                            height="2rem"
                            width="5rem"
                            rounded="rounded-full"
                        />
                    </div>
                </section>
                <section class="space-y-3">
                    <Rectangle height="1rem" width="9rem" rounded="rounded-md" />
                    <Text :lines="3" line-height="0.75rem" last-line-width="45%" />
                </section>
            </div>

            <div v-else-if="!nodeSchema" class="opacity-70">
                Нет данных о схеме
            </div>

            <template v-else>

                <!-- INPUT -->
                <section class="space-y-3">
                    <h3 class="t-body">
                        Входные данные
                    </h3>

                    <div
                        v-if="!hasSchemaTree(nodeSchema.inputSchema)"
                        class="opacity-70"
                    >
                        Нет входных данных
                    </div>

                    <div
                        v-else
                        class="flex flex-wrap items-center gap-2"
                    >
                        <SchemaTree :schema="nodeSchema.inputSchema" />
                    </div>
                </section>

                <!-- OUTPUT -->
                <section class="space-y-3">
                    <h3 class="t-body">
                        Выходные данные
                    </h3>

                    <div
                        v-if="!hasSchemaTree(nodeSchema.outputSchema)"
                        class="opacity-70"
                    >
                        Нет выходных данных
                    </div>

                    <div
                        v-else
                        class="flex flex-wrap items-center gap-2"
                    >
                        <SchemaTree :schema="nodeSchema.outputSchema" />
                    </div>
                </section>
            </template>
        </div>
    </Modal>
</template>
