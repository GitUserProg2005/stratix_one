<script setup>
import Modal from '@/Components/Modal.vue';
import { ref, onMounted, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { getBezierPath } from '@vue-flow/core';
import { flattenSchema, flattenArrayPaths } from '../utils/flattenSchema';
import { buildAST } from '../utils/buildAST';
import { isValidSchema } from '../utils/isValidSchema';
import { resolveDynamicSchema } from '../utils/resolveDynamicSchema';
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

    lock: Object,
    acquireLock: Function,
    releaseLock: Function,
});

const isSaving = ref(false);

const mappings = ref({});

const resolvedSourceSchema = computed(() => {
    if (!props.sourceSchema) return null;

    if (props.sourceSchema.dynamic_output) {
        return {
            ...props.sourceSchema,
            outputSchema: resolveDynamicSchema(
                props.sourceNode,
                'output',
                props.sourceSchema,
            ),
        };
    }

    return props.sourceSchema;
});

const resolvedTargetSchema = computed(() => {
    if (!props.targetSchema) return null;

    if (props.targetSchema.dynamic_input) {
        return {
            ...props.targetSchema,
            inputSchema: resolveDynamicSchema(
                props.targetNode,
                'input',
                props.targetSchema,
            ),
        };
    }

    return props.targetSchema;
});

const sourceFields = computed(() => {
    return flattenSchema(resolvedSourceSchema.value?.outputSchema);
});

const arraySourceFields = computed(() => {
    return flattenArrayPaths(resolvedSourceSchema.value?.outputSchema);
});

const targetInputSchema = computed(() => {
    const mode = props.targetNode?.data?.config?.mode;

    if (mode && resolvedTargetSchema.value?.inputSchemaModes?.[mode]) {
        const schema = resolvedTargetSchema.value.inputSchemaModes[mode];
        return isValidSchema(schema) ? schema : null;
    }

    const schema = resolvedTargetSchema.value?.inputSchema;
    return isValidSchema(schema) ? schema : null;
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

    if (!target) return;

    mappings.value[target] = source;

    saveTransform();
}

const isOpen = ref(false);
const currentUserId = usePage().props.auth.user?.id;

const isLockedByOther = computed(() =>
    props.lock && props.lock.userId !== currentUserId
);

function toggleModal() {
    if (isLockedByOther.value) return;

    if (!isOpen.value) {
        if (props.acquireLock && !props.acquireLock()) return;
        isOpen.value = true;
        return;
    }

    props.releaseLock?.();
    isOpen.value = false;
}

async function saveTransform() {
    console.log('saving', mappings.value);

    isSaving.value = true;

    try {
        const ast = buildAST(
            targetInputSchema.value,
            mappings.value
        );

        console.log('AST: ', ast);

        await axios.post(route('edge.transform.update', props.id), {
            transform: {
                ast: ast,
                mappings: mappings.value,
            }
        });
    } catch (e) {
        console.error('Ошибка сохранения transform', e);
    } finally {
        isSaving.value = false;
    }
}

function astToMappings(ast, prefix = '', output = {}) {
    if (!ast) return output;

    if (ast.type === 'group') {
        const current = ast.name ? (prefix ? `${prefix}.${ast.name}` : ast.name) : prefix;

        for (const field of ast.fields || []) {
            astToMappings(field, current, output);
        }

        return output;
    }

    if (ast.type === 'array') {
        const arrayPath = ast.name ? (prefix ? `${prefix}.${ast.name}` : ast.name) : prefix;

        if (arrayPath && ast.from) {
            output[arrayPath] = ast.from;
        }

        const itemPrefix = arrayPath ? `${arrayPath}[]` : prefix;
        astToMappings(ast.items, itemPrefix, output);

        return output;
    }

    if (ast.type === 'field') {
        const path = ast.key ? (prefix ? `${prefix}.${ast.key}` : ast.key) : null;

        if (path && ast.from) {
            output[path] = ast.from;
        }
    }

    return output;
}

onMounted(() => {
    console.log('EDGE: ', props.edge);

    const transform = props.edge?.data?.transform;
    if (!transform) return;

    if (transform.mappings && typeof transform.mappings === 'object') {
        mappings.value = transform.mappings;
        return;
    }

    if (transform.ast) {
        mappings.value = astToMappings(transform.ast);
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
            :stroke="lock?.color ?? 'rgba(120,120,152,0.5)'"
            :stroke-width="lock ? 3 : 2"
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
                :disabled="isLockedByOther"
                :class="{ 'opacity-50 pointer-events-none': isLockedByOther }"
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
                                v-if="!targetInputSchema"
                                class="opacity-70"
                            >
                                Нет входных данных для маппинга
                            </div>

                            <SchemaTree
                                v-else
                                :schema="targetInputSchema"
                                :mappings="mappings"
                                :source-fields="sourceFields"
                                :array-source-fields="arraySourceFields"
                                @update="updateMapping"
                            />
                        </div>
                    </div>

                    <!-- DEBUG -->
                    <pre class="text-xs bg-content my-4 rounded-xl p-4">
                        {{ mappings }}
                    </pre>

                    <button @click="saveTransform" class="primary-btn">
                        {{ isSaving ? 'Идет сохранение...' : 'Сохранить' }}
                    </button>
                </div>
            </Modal>
        </foreignObject>
    </g>  
</template>