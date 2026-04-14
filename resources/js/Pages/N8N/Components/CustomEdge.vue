<script setup>
import Modal from '@/Components/Modal.vue';
import { ref, onMounted, computed, watch } from 'vue';
import { getBezierPath } from '@vue-flow/core';
import axios from 'axios';

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

const isSaving = ref(false);

const mappings = ref({});

const sourceFields = computed(() => 
    Object.keys(props.sourceSchema?.outputSchema || {})
);

const targetFields = computed(() =>
    Object.keys(props.targetSchema?.inputSchema || {})
);

function updateMapping() {
    emit('add-mapping', {
        edgeId: props.id,
        mappings: mappings.value
    });

    saveTransform();
}

const isOpen = ref(false);

function toggleModal() {
    isOpen.value = !isOpen.value;
}

async function saveTransform() {
    console.log('saving', mappings.value);
    isSaving.value = true;

    try {
        await axios.post(route('edge.transform.update', props.id), {
            transform: {
                mappings: mappings.value
            }
        });
    } catch (e) {
        console.error('Ошибка сохранения transform', e);
    } finally {
        isSaving.value = false;
    }
}

onMounted(() => {
    if (props.edge?.data?.transform?.mappings) {
        mappings.value = props.edge.data.transform.mappings;
    }
});
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
                <div class="flex items-center justify-between">
                    <h3 class="title-2 mb-4">Поток данных</h3>
                </div>
                
                <!--Selection для маппинга-->
                <div>
                    <div>
                        <h4 class="mb-2">Куда</h4>

                        <div
                            v-for="field in targetFields"
                            :key="field"
                            class="p-2 bg-content-accent"
                        >
                            {{ field }}

                            <select v-model="mappings[field]"
                                class="select-input text-white"
                            >
                                <option value="">
                                    Не выбрано
                                </option>

                                <option 
                                    v-for="source in sourceFields"
                                    :key="source"
                                    :value="source"
                                >
                                    {{ source }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- DEBUG -->
                <pre class="text-xs bg-gray-900 my-4 rounded-xl p-4">
                    {{ mappings }}
                </pre>

                <button
                    @click="updateMapping"
                    class="primary-btn"
                >
                    {{ isSaving ? 'Идет сохранение...' : 'Сохранить' }}
                </button>
            </div>
        </Modal>
        </foreignObject>
    </g>  
</template>