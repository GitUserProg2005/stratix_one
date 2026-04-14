<script setup>
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';
import { getBezierPath } from '@vue-flow/core';

const emit = defineEmits(['add-mapping']);

const props = defineProps({
    id: String,
    source: String,
    target: String,
    sourceX: Number,
    sourceY: Number,
    targetX: Number,
    targetY: Number,
    sourcePosition: String,
    targetPosition: String,

    sourceSchema: Object,
    targetSchema: Object,

    sourceNode: Object,
    targetNode: Object,

    edge: Object,
});

const selectedFrom = ref(null);
const selectedTo = ref(null);

const mappings = ref([]);

function selectFrom(field) {
    selectedFrom.value = field;
}

function selectTo(field) {
    selectedTo.value = field;
}

function addMapping() {
    if (!selectedFrom.value || !selectedTo.value) return;

    const mapping = {
        from: selectedFrom.value,
        to: selectedTo.value,
    };

    mappings.value.push(mapping);

    emit('add-mapping', {
        edgeId: props.id,
        mapping,
        all: mappings.value,
    });

    selectedFrom.value = null;
    selectedTo.value = null;
}

const isOpen = ref(false);

function toggleModal() {
    isOpen.value = !isOpen.value;
}
</script>

<template>
    <g>
        <!-- линия -->
        <path
            :d="getBezierPath({
                sourceX,
                sourceY,
                targetX,
                targetY,
            })[0]"
            stroke="rgba(120,120,152,0.5)"
            stroke-width="2"
            fill="none"
        />

        <!-- центр (пока просто тест UI) -->
        <foreignObject
            :x="getBezierPath({
                sourceX,
                sourceY,
                targetX,
                targetY,
                sourcePosition,
                targetPosition,
            })[1] - 12"
            :y="getBezierPath({
                sourceX,
                sourceY,
                targetX,
                targetY,
                sourcePosition,
                targetPosition,
            })[2] - 12"
            width="24"
            height="24"
        >
            <button
                class="w-6 h-6 bg-content-ice rounded-full flex items-center justify-center text-xs"
                @click.stop="toggleModal"
            >
                <i class="fa-solid fa-plus"></i>
            </button>

            <Modal :show="isOpen" @close="toggleModal">
            <div class="p-4">

                <h3 class="text-lg mb-4">Mapping</h3>

                <div class="grid grid-cols-2 gap-4">

                    <!-- SOURCE -->
                    <div>
                        <h4 class="mb-2">FROM</h4>

                        <div
                            v-for="(type, field) in sourceSchema?.outputSchema"
                            :key="field"
                            class="p-2 mb-2 bg-gray-100 cursor-pointer"
                            :class="{ 'bg-blue-200': selectedFrom === field }"
                            @click="selectFrom(field)"
                        >
                            {{ field }}
                        </div>
                    </div>

                    <!-- TARGET -->
                    <div>
                        <h4 class="mb-2">TO</h4>

                        <div
                            v-for="(type, field) in targetSchema?.inputSchema"
                            :key="field"
                            class="p-2 mb-2 bg-gray-100 cursor-pointer"
                            :class="{ 'bg-green-200': selectedTo === field }"
                            @click="selectTo(field)"
                        >
                            {{ field }}
                        </div>
                    </div>

                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button @click="addMapping" class="px-3 py-1 bg-black text-white">
                        Add
                    </button>
                </div>

                <!-- DEBUG -->
                <pre class="mt-4 text-xs">
                    {{ mappings }}
                </pre>

            </div>
        </Modal>
        </foreignObject>
    </g>  
</template>