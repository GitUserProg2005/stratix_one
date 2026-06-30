<script setup>
import Carousel from '@/Components/Carousel.vue';
import Graph3D from '@/Pages/N8N/Components/Graph3D.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    workflows: {
        type: Array,
        default: () => [],
    },
    authorName: {
        type: String,
        required: true,
    },
});

function graphProps(item) {
    return {
        nodes: item.nodes ?? [],
        edges: item.edges ?? [],
    };
}
</script>

<template>
    <section v-if="workflows.length" class="space-y-4">
        <h2 class="dashboard-row-title flex items-center gap-2 text-sm">
            <i class="fa-solid fa-diagram-project text-[var(--accent)]" />
            Другие автоматизации от {{ authorName }}
        </h2>

        <Carousel
            :items="workflows"
            :autoplay="false"
            :slides-per-view="1"
            :space-between="16"
            :pagination="workflows.length > 1"
            spaced-layout
            :breakpoints="{
                640: { slidesPerView: 2, spaceBetween: 16 },
                1024: { slidesPerView: 3, spaceBetween: 20 },
            }"
        >
            <template #default="{ item }">
                <Link
                    :href="route('catalog.detail', item.id)"
                    class="content-card block overflow-hidden rounded-2xl border border-transparent transition hover:border-[var(--border-input)]"
                >
                    <div class="relative h-36 min-h-0 overflow-hidden">
                        <Graph3D
                            :key="`additional-graph-${item.id}`"
                            v-bind="graphProps(item)"
                            class="size-full"
                        />
                        <span
                            v-if="item.category"
                            class="badge badge-completed absolute left-2 top-2 flex items-center gap-1 text-[10px]"
                        >
                            <i class="fa-solid fa-layer-group" />
                            {{ item.category.title }}
                        </span>
                    </div>

                    <div class="space-y-2 p-3">
                        <div class="dashboard-row-title truncate text-sm">
                            {{ item.title }}
                        </div>

                        <span class="label-accent inline-flex items-center gap-1.5 text-[10px]">
                            <i class="fa-solid fa-download" />
                            {{ item.downloads }} установок
                        </span>
                    </div>
                </Link>
            </template>
        </Carousel>
    </section>
</template>
