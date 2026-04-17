<script setup>
import Modal from '@/Components/Modal.vue';
import { ref, onMounted, computed, watch } from 'vue';
import { getBezierPath } from '@vue-flow/core';
import { flattenSchema } from '../utils/flattenSchema';
import { buildAST } from '../utils/buildAST';
import SchemaTree from './SchemaTree.vue';
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

const resolvedSourceSchema = computed(() => {
    if (!props.sourceSchema) return null;

    if (props.sourceSchema.dynamic) {
        return {
            ...props.sourceSchema,
            outputSchema: resolveDynamicSchema(
                props.sourceNode,
                'output'
            )
        }
    }

    return props.sourceSchema;
});

function resolveDynamicSchema(node, type = 'output') {
    if (!node?.data?.config) return null;

    const config = node.data.config;

    if (type === 'output') {
        return config.output || config.request || null;
    }

    return null;
}

const sourceFields = computed(() => { 
    //Object.keys(props.sourceSchema?.outputSchema || {})
    return flattenSchema(resolvedSourceSchema.value?.outputSchema);
});

// const targetFields = computed(() => {
//     // Object.keys(props.targetSchema?.inputSchema || {})
//     return flattenSchema(props.targetSchema?.inputSchema);
// });

function updateMapping({ target, source }) {
    // emit('add-mapping', {
    //     edgeId: props.id,
    //     mappings: mappings.value
    // });

    mappings.value[target] = source;

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
        const ast = buildAST(
            props.targetSchema?.inputSchema,
            mappings.value
        );

        await axios.post(route('edge.transform.update', props.id), {
            transform: {
                ast: ast
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

                            <SchemaTree 
                                :schema="targetSchema?.inputSchema"
                                :mappings="mappings"
                                :sourceFields="sourceFields"
                                @update="updateMapping"
                            />
                        </div>
                    </div>

                    <!-- DEBUG -->
                    <pre class="text-xs bg-gray-900 my-4 rounded-xl p-4">
                        {{ mappings }}
                    </pre>

                    <pre>
                        source schema:
                        {{ resolvedSourceSchema }}
                    </pre>

                    <pre>
                        target schema:
                        {{ targetSchema }}
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