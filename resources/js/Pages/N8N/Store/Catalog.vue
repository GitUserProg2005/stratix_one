<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Graph3D from '@/Pages/N8N/Components/Graph3D.vue';
import HeadlessSelect from '@/Components/HeadlessSelect.vue';
import Avatar from '@/Components/Avatar.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    catalogWorkflows: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ workflow_category_id: null }),
    },
});

const selectedCategoryId = computed({
    get: () => props.filters.workflow_category_id ?? '',
    set: (value) => {
        router.get(
            route('catalog.index'),
            { workflow_category_id: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    },
});

const categoryOptions = computed(() => [
    { label: 'Все категории', value: '' },
    ...props.categories.map((category) => ({
        label: category.title,
        value: category.id,
    })),
]);

function graphProps(item) {
    return {
        nodes: item.nodes ?? [],
        edges: item.edges ?? [],
    };
}
</script>

<template>
    <Head title="Каталог workflow" />

    <AppLayout>
        <div class="mx-auto max-w-6xl p-4 lg:p-6">
            <div class="mb-6 flex items-start gap-3">
                <span class="dashboard-inset flex size-10 shrink-0 items-center justify-center rounded-xl">
                    <i class="fa-solid fa-bars-staggered text-[var(--accent)]" />
                </span>
                <div>
                    <h1 class="title mb-1">Каталог workflow</h1>
                    <p class="context">
                        Готовые сценарии от сообщества — установите и адаптируйте под себя.
                    </p>
                </div>
            </div>

            <div class="mb-6 max-w-xs">
                <h2 class="dashboard-row-title mb-2 flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-filter text-[var(--accent)]" />
                    Категория
                </h2>
                <HeadlessSelect
                    v-model="selectedCategoryId"
                    :options="categoryOptions"
                    button-class="select-input w-full"
                    placeholder="Все категории"
                />
            </div>

            <div
                v-if="catalogWorkflows.length"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
            >
                <Link
                    v-for="item in catalogWorkflows"
                    :key="item.id"
                    :href="route('catalog.detail', item.id)"
                    class="content-card group overflow-hidden rounded-2xl border border-transparent transition hover:border-[var(--border-input)]"
                >
                    <div class="relative h-44 min-h-0 overflow-hidden">
                        <Graph3D
                            :key="`catalog-graph-${item.id}`"
                            v-bind="graphProps(item)"
                            class="size-full"
                        />
                        <span
                            v-if="item.category"
                            class="badge badge-completed absolute left-3 top-3 flex items-center gap-1.5"
                        >
                            <i class="fa-solid fa-layer-group text-[10px]" />
                            {{ item.category.title }}
                        </span>
                    </div>

                    <div class="space-y-3 p-4">
                        <div class="title-2 flex items-start gap-2 truncate">
                            <span class="truncate">{{ item.title }}</span>
                        </div>

                        <p
                            v-if="item.description"
                            class="context line-clamp-2 text-xs opacity-80"
                        >
                            {{ item.description }}
                        </p>

                        <div class="flex items-center justify-between gap-3 border-t border-[var(--border-input)] pt-3">
                            <div v-if="item.author" class="flex min-w-0 items-center gap-2">
                                <Avatar
                                    :name="item.author.name"
                                    :src="item.author.avatar_url"
                                    :user-id="item.author.id"
                                    size="sm"
                                    no-link
                                />
                                <span class="context flex min-w-0 items-center gap-1.5 truncate text-xs">
                                    {{ item.author.name }}
                                </span>
                            </div>

                            <span class="context flex shrink-0 items-center gap-1.5 text-xs opacity-80">
                                <i class="fa-solid fa-download text-[var(--accent)]" />
                                {{ item.downloads }}
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-else class="dashboard-inset flex flex-col items-center gap-3 rounded-2xl p-8 text-center">
                <i class="fa-solid fa-box-open text-3xl text-[var(--accent)] opacity-80" />
                <p class="context opacity-70">
                    В каталоге пока нет workflow.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
