<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

import Modal from '@/Components/Modal.vue';
import Rectangle from '@/Components/Skeleton/Rectangle.vue';
import ModalPricingRate from '@/Components/PricingRate/ModalPricingRate.vue';

const emit = defineEmits(['onSelectedNodeType']);

const nodeTypes = ref([]);
const isLoading = ref(true);
const showModal = ref(false);
const searchQuery = ref('');
const currentNodeType = ref(null);

const page = usePage();

const userRateId = computed(() => page.props.auth?.user?.rate?.id ?? null);

const filteredNodeTypes = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();

    if (!q) {
        return nodeTypes.value;
    }

    return nodeTypes.value.filter((nodeType) => {
        return nodeType.name.toLowerCase().includes(q) || nodeType.type.toLowerCase().includes(q);
    });
});

const selectedNodeName = computed(() => {
    if (!currentNodeType.value) {
        return null;
    }

    return nodeTypes.value.find((nodeType) => nodeType.type === currentNodeType.value)?.name ?? currentNodeType.value;
});

function isNodeAvailable(nodeType) {
    const rateIds = nodeType.rate_ids ?? [];

    if (!rateIds.length) {
        return true;
    }

    if (!userRateId.value) {
        return false;
    }

    return rateIds.includes(userRateId.value);
}

async function getNodeTypes() {
    isLoading.value = true;

    try {
        const response = await axios.get(route('get.node.types'));

        if (response.data.result === 'ok') {
            nodeTypes.value = response.data.nodeTypes;
        }
    } catch (error) {
        console.error('Error fetching node types:', error);
    } finally {
        isLoading.value = false;
    }
}

function openModal() {
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    searchQuery.value = '';
}

function selectNodeType(nodeType) {
    if (!isNodeAvailable(nodeType)) {
        return;
    }

    emit('onSelectedNodeType', nodeType.type);

    currentNodeType.value = nodeType.type;
    closeModal();
}

onMounted(() => {
    getNodeTypes();
});
</script>

<template>
    <div>
        <button type="button" class="select-input mt-2 flex w-full items-center justify-between gap-2" @click="openModal">
            <span>{{ selectedNodeName ?? 'Выбрать ноду' }}</span>
            <i class="fa-solid fa-chevron-down text-xs opacity-60" />
        </button>

        <div v-if="currentNodeType" class="mt-3">
            <h4 class="label-glass">Выбранный тип: {{ currentNodeType }}</h4>
        </div>

        <Modal :show="showModal" max-width="5xl" @close="closeModal">
            <div class="custom-scroll max-h-[90vh] space-y-4 overflow-y-auto p-4 md:p-6">
                <div class="flex flex-row items-center justify-between gap-3">
                    <h2 class="title-2">Выбор ноды</h2>
                    
                    <button type="button" class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit" aria-label="Закрыть" @click="closeModal">
                        <i class="fa-solid fa-xmark text-xl" />
                    </button>
                </div>

                <input
                    v-model="searchQuery"
                    type="text"
                    class="search-input w-full px-4 py-2"
                    placeholder="Поиск нод по типам"
                />

                <div v-if="isLoading" class="grid grid-cols-4 gap-4">
                    <Rectangle
                        v-for="i in 8"
                        :key="i"
                        height="8rem"
                        rounded="rounded-2xl"
                    />
                </div>

                <div v-else-if="!filteredNodeTypes.length" class="content-outline p-6 text-center">
                    <p class="context">Ничего не найдено</p>
                </div>

                <div v-else class="grid grid-cols-4 gap-4">
                    <template v-for="nodeType in filteredNodeTypes" :key="nodeType.type">
                        <button
                            v-if="isNodeAvailable(nodeType)"
                            type="button"
                            class="content-card overflow-hidden rounded-2xl border border-transparent p-4 text-left transition hover:border-[var(--border-input)]"
                            @click="selectNodeType(nodeType)"
                        >
                            <h4 class="title-3 mb-1">{{ nodeType.name }}</h4>
                            <p v-if="nodeType.description" class="context mb-3 line-clamp-2 text-xs opacity-80">
                                {{ nodeType.description }}
                            </p>
                            <span class="badge badge-success">Доступно</span>
                        </button>

                        <div
                            v-else
                            class="content-card flex flex-col overflow-hidden rounded-2xl border border-transparent p-4 opacity-75"
                        >
                            <h4 class="title-3 mb-1">{{ nodeType.name }}</h4>
                            <p v-if="nodeType.description" class="context mb-3 line-clamp-2 flex-1 text-xs opacity-80">
                                {{ nodeType.description }}
                            </p>
                            <div class="mt-auto flex flex-wrap items-center gap-2">
                                <span class="badge badge-pending">Недоступно</span>
                                <ModalPricingRate button-label="Посмотреть тарифы" />
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </Modal>
    </div>
</template>
