<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Graph3D from '@/Pages/N8N/Components/Graph3D.vue';
import Avatar from '@/Components/Avatar.vue';
import BackButton from '@/Components/BackButton.vue';
import AdditionalWorkflows from './AdditionalWorkflows.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    catalogWorkflow: {
        type: Object,
        required: true,
    },
    additionalWorkflows: {
        type: Array,
        default: () => [],
    },
});

const scrollY = ref(0);
const isInstalling = ref(false);

const GRAPH_MAX = 176;
const GRAPH_MIN = 72;
const SHRINK_RANGE = 104;

let scrollEl = null;

const graphHeight = computed(() => {
    const delta = Math.min(scrollY.value, SHRINK_RANGE);

    return GRAPH_MAX - delta * ((GRAPH_MAX - GRAPH_MIN) / SHRINK_RANGE);
});

const graphProps = computed(() => ({
    nodes: props.catalogWorkflow.nodes ?? [],
    edges: props.catalogWorkflow.edges ?? [],
}));

function onScroll() {
    scrollY.value = scrollEl?.scrollTop ?? 0;
}

function installWorkflow() {
    if (isInstalling.value) {
        return;
    }

    isInstalling.value = true;

    router.post(route('catalog.install', props.catalogWorkflow.id), {}, {
        onFinish: () => {
            isInstalling.value = false;
        },
    });
}

onMounted(() => {
    scrollEl = document.querySelector('main');
    scrollEl?.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => {
    scrollEl?.removeEventListener('scroll', onScroll);
});
</script>

<template>
    <Head :title="catalogWorkflow.title" />

    <AppLayout>
        <div class="relative w-full">
            <div class="absolute left-4 top-4 z-30">
                <BackButton back-url="catalog.index" />
            </div>

            <div
                class="sticky top-0 z-20 w-full overflow-hidden bg-content transition-[height] duration-100 ease-out"
                :style="{ height: `${graphHeight}px` }"
            >
                <Graph3D
                    :key="`detail-graph-${catalogWorkflow.id}`"
                    v-bind="graphProps"
                    class="size-full"
                />
            </div>

            <div class="flex items-center justify-between gap-4 border-b border-[var(--border-input)] px-4 py-4">
                <div class="min-w-0 space-y-2">
                    <h1 class="title-2 truncate">
                        {{ catalogWorkflow.title }}
                    </h1>

                    <span class="label-accent inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-download text-[10px]" />
                        {{ catalogWorkflow.downloads }} установок
                    </span>
                </div>

                <div v-if="catalogWorkflow.author" class="flex shrink-0 items-center gap-2">
                    <div class="min-w-0 text-right">
                        <div class="context truncate text-xs opacity-70">Автор</div>
                        <div class="dashboard-row-title truncate text-sm">
                            {{ catalogWorkflow.author.name }}
                        </div>
                    </div>
                    <Avatar
                        :name="catalogWorkflow.author.name"
                        :src="catalogWorkflow.author.avatar_url"
                        :user-id="catalogWorkflow.author.id"
                        size="sm"
                    />
                </div>
            </div>

            <div class="space-y-6 px-4 py-6">
                <div v-if="catalogWorkflow.category" class="flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-[var(--accent)]" />
                    <span class="badge badge-completed">
                        {{ catalogWorkflow.category.title }}
                    </span>
                </div>

                <p v-if="catalogWorkflow.description" class="context max-w-3xl leading-relaxed">
                    {{ catalogWorkflow.description }}
                </p>

                <div class="dashboard-inset max-w-xl space-y-3 rounded-2xl p-4">
                    <h2 class="dashboard-row-title text-sm">Установка</h2>
                    <p class="context text-sm opacity-80">
                        Создаст копию workflow в вашем аккаунте — ноды, связи и конфигурация сохранятся.
                    </p>
                    <button
                        type="button"
                        class="primary-btn flex items-center gap-2"
                        :disabled="isInstalling"
                        @click="installWorkflow"
                    >
                        Установить workflow
                        <i class="fa-solid fa-cloud-arrow-down" />
                    </button>
                </div>

                <AdditionalWorkflows
                    v-if="catalogWorkflow.author"
                    :workflows="additionalWorkflows"
                    :author-name="catalogWorkflow.author.name"
                />

                <div class="h-[40vh]" aria-hidden="true" />
            </div>
        </div>
    </AppLayout>
</template>
