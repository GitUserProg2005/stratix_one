<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed, nextTick } from 'vue';
import axios from 'axios';
import JsonEditor from 'vue3-ts-jsoneditor';
import { Swiper, SwiperSlide } from 'swiper/vue';
import Rectangle from '@/Components/Skeleton/Rectangle.vue';

const props = defineProps({
    workflowId: {
        type: [Number, String],
        required: true,
    },
    roomId: {
        type: [Number, String],
        default: null,
    },
});

const contexts = ref([]);
const selectedContextId = ref(null);
const editorText = ref('{}');
const isLoading = ref(false);
const isSaving = ref(false);
const editorWrap = ref(null);
const editorHeight = ref(280);

const isDark = computed(() => document.documentElement.classList.contains('dark'));

let resizeObserver = null;

const syncEditorHeight = () => {
    const el = editorWrap.value;
    if (!el) {
        return;
    }

    const height = Math.floor(el.clientHeight);
    if (height > 80) {
        editorHeight.value = height;
    }
};

const loadContexts = async () => {
    isLoading.value = true;
    try {
        const { data } = await axios.get(route('ai-chat.contexts.index'), {
            params: { workflow_id: props.workflowId },
        });
        contexts.value = Array.isArray(data) ? data : [];

        if (!selectedContextId.value && contexts.value.length) {
            const firstId = contexts.value[0].id;
            selectedContextId.value = Number(firstId);
            editorText.value = contexts.value[0].body ?? '{}';
        }
    } catch (error) {
        console.error('Failed to load contexts', error);
        contexts.value = [];
    } finally {
        isLoading.value = false;
        await nextTick();
        syncEditorHeight();
    }
};

const loadRoomContext = async () => {
    if (!props.roomId) {
        return;
    }

    try {
        const { data } = await axios.get(route('ai-chat.context', props.roomId));
        if (data?.id) {
            selectedContextId.value = data.id;
            editorText.value = data.body ?? '{}';
            return;
        }

        if (contexts.value.length && !selectedContextId.value) {
            selectedContextId.value = Number(contexts.value[0].id);
            editorText.value = contexts.value[0].body ?? '{}';
        }
    } catch (error) {
        console.error('Failed to load room context', error);
    }
};

const selectContext = async (id) => {
    selectedContextId.value = Number(id);
    const ctx = contexts.value.find((item) => Number(item.id) === Number(id));
    editorText.value = ctx?.body ?? '{}';

    if (!props.roomId) {
        return;
    }

    try {
        await axios.patch(route('ai-chat.rooms.context.update', props.roomId), {
            context_id: selectedContextId.value,
        });
    } catch (error) {
        console.error('Failed to update room context', error);
    }
};

const createContext = async () => {
    if (isSaving.value) {
        return;
    }

    isSaving.value = true;
    try {
        const { data } = await axios.post(route('ai-chat.contexts.store'), {
            body: '{}',
            workflow_id: props.workflowId,
        });
        await loadContexts();
        if (data?.id) {
            await selectContext(data.id);
        }
    } catch (error) {
        console.error('Failed to create context', error);
    } finally {
        isSaving.value = false;
    }
};

const saveContext = async () => {
    if (!selectedContextId.value || isSaving.value) {
        return;
    }

    isSaving.value = true;
    try {
        await axios.put(route('ai-chat.contexts.update', selectedContextId.value), {
            body: editorText.value,
        });
        const idx = contexts.value.findIndex((item) => Number(item.id) === Number(selectedContextId.value));
        if (idx !== -1) {
            contexts.value[idx].body = editorText.value;
        }
    } catch (error) {
        console.error('Failed to save context', error);
    } finally {
        isSaving.value = false;
    }
};

const deleteContext = async () => {
    if (!selectedContextId.value || isSaving.value) {
        return;
    }

    const id = selectedContextId.value;
    isSaving.value = true;
    try {
        await axios.delete(route('ai-chat.contexts.destroy', id));
        await loadContexts();
        if (contexts.value.length) {
            await selectContext(contexts.value[0].id);
        } else {
            selectedContextId.value = null;
            editorText.value = '{}';
            if (props.roomId) {
                await axios.patch(route('ai-chat.rooms.context.update', props.roomId), {
                    context_id: null,
                });
            }
        }
    } catch (error) {
        console.error('Failed to delete context', error);
    } finally {
        isSaving.value = false;
    }
};

watch(() => props.workflowId, async () => {
    await loadContexts();
    await loadRoomContext();
});

watch(() => props.roomId, () => {
    loadRoomContext();
});

onMounted(async () => {
    await loadContexts();
    await loadRoomContext();

    if (editorWrap.value) {
        resizeObserver = new ResizeObserver(() => syncEditorHeight());
        resizeObserver.observe(editorWrap.value);
        syncEditorHeight();
    }
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
});
</script>

<template>
    <section class="dashboard-inset flex h-full min-h-0 flex-col gap-2 overflow-hidden">
        <div class="flex shrink-0 items-center justify-between gap-2">
            <h3 class="title-3">Контексты</h3>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="dashboard-icon-slot transition hover:bg-[color-mix(in_srgb,var(--accent)_22%,transparent)] disabled:opacity-50"
                    title="Добавить"
                    :disabled="isSaving"
                    @click="createContext"
                >
                    <i class="fa-solid fa-plus text-[var(--accent)]" />
                </button>
                <button
                    type="button"
                    class="dashboard-icon-slot transition hover:bg-[color-mix(in_srgb,var(--accent)_22%,transparent)] disabled:opacity-50"
                    title="Сохранить"
                    :disabled="isSaving || !selectedContextId"
                    @click="saveContext"
                >
                    <i class="fa-solid fa-floppy-disk text-[var(--accent)]" />
                </button>
                <button
                    type="button"
                    class="dashboard-icon-slot transition hover:bg-[color-mix(in_srgb,#ef4444_18%,transparent)] disabled:opacity-50"
                    title="Удалить"
                    :disabled="isSaving || !selectedContextId"
                    @click="deleteContext"
                >
                    <i class="fa-solid fa-trash text-red-400" />
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="flex shrink-0 gap-2" aria-busy="true">
            <Rectangle
                v-for="i in 3"
                :key="i"
                height="2.25rem"
                width="6rem"
                rounded="rounded-xl"
            />
        </div>
        <Swiper
            v-else-if="contexts.length"
            :slides-per-view="'auto'"
            :space-between="8"
            class="context-swiper shrink-0"
        >
            <SwiperSlide
                v-for="(ctx, index) in contexts"
                :key="ctx.id"
                class="!w-auto"
            >
                <button
                    type="button"
                    class="agent-chip"
                    :class="{ 'agent-chip--active': Number(selectedContextId) === Number(ctx.id) }"
                    @click="selectContext(ctx.id)"
                >
                    #{{ index + 1 }}
                </button>
            </SwiperSlide>
        </Swiper>
        <p v-else class="t-mini shrink-0">
            Нет контекстов — создайте первый
        </p>

        <div
            ref="editorWrap"
            class="context-editor min-h-0 flex-1"
        >
            <JsonEditor
                v-if="selectedContextId && editorHeight > 0"
                v-model:text="editorText"
                mode="text"
                :height="editorHeight"
                :dark-theme="isDark"
                :navigation-bar="false"
                :full-width-button="false"
                class="json-editor-inset h-full w-full"
            />
            <p v-else class="t-mini flex h-full items-center justify-center">
                Выберите или создайте контекст
            </p>
        </div>
    </section>
</template>

<style scoped>
.context-swiper {
    width: 100%;
}

.agent-chip {
    @apply rounded-xl border px-4 py-2 text-sm font-medium transition;
    border-color: var(--border-input);
    background-color: transparent;
    color: var(--content-primary);
}

.agent-chip:hover {
    border-color: color-mix(in srgb, var(--accent) 50%, transparent);
}

.agent-chip--active {
    border-color: var(--accent);
    background-color: color-mix(in srgb, var(--accent) 18%, transparent);
}

.context-editor {
    overflow: hidden;
}

.json-editor-inset {
    --jse-theme-color: var(--accent);
    --jse-theme-color-highlight: var(--accent-hover);
    --jse-background-color: var(--bg-card);
    --jse-text-color: var(--content-primary);
    --jse-text-color-inverse: #ffffff;
    --jse-main-border: none;
    --jse-menu-color: var(--content-primary);
    --jse-menu-button-size: 28px;
    --jse-panel-background: color-mix(in srgb, var(--content-primary) 5%, transparent);
    --jse-panel-color: var(--content-primary);
    --jse-panel-border: 1px solid color-mix(in srgb, var(--content-primary) 8%, transparent);
    --jse-panel-button-background-highlight: color-mix(in srgb, var(--accent) 18%, transparent);
    --jse-navigation-bar-background: transparent;
    --jse-font-size: 13px;
    --jse-font-size-mono: 13px;
    --jse-padding: 8px;
    --jse-key-color: var(--content-primary);
    --jse-value-color: var(--content-primary);
    --jse-value-color-number: var(--accent);
    --jse-value-color-string: #7dd3a0;
    --jse-delimiter-color: color-mix(in srgb, var(--content-primary) 35%, transparent);
    --jse-button-primary-background: var(--accent);
    --jse-button-primary-background-highlight: var(--accent-hover);
}

.json-editor-inset :deep(.jse-main) {
    height: 100% !important;
    min-height: 100%;
    border: 1px solid color-mix(in srgb, var(--content-primary) 8%, transparent);
    border-radius: 12px;
    background: var(--bg-card);
    display: flex;
    flex-direction: column;
}

.json-editor-inset :deep(.jse-menu) {
    flex-shrink: 0;
    border-radius: 12px 12px 0 0;
    background: color-mix(in srgb, var(--content-primary) 5%, transparent);
    border-bottom: 1px solid color-mix(in srgb, var(--content-primary) 8%, transparent);
}

.json-editor-inset :deep(.jse-contents),
.json-editor-inset :deep(.jse-text-mode) {
    flex: 1 1 0;
    min-height: 0;
}

.json-editor-inset :deep(.cm-editor),
.json-editor-inset :deep(.cm-scroller) {
    height: 100% !important;
    min-height: 0;
    background: var(--bg-card);
}
</style>
