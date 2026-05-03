<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

import { nextTick, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

import { GridStack } from 'gridstack';
import 'gridstack/dist/gridstack.css';

import axios from 'axios';

const gridContainer = ref(null);
let grid = null;

const widgets = ref([
  { id: 1, x: 0, y: 0, w: 2, h: 2, content: 'График продаж' },
  { id: 2, x: 2, y: 1, w: 2, h: 2, content: 'Инсайт от AI' }
]);

async function loadWidgets() {
    try {
        await axios.get(route('dashboard.widgets'));
    } catch (e) {
        console.error(e);
    }
}

onMounted(async () => {
    loadWidgets();

    await nextTick();

    grid = GridStack.init(
        {
            column: 4,
            cellHeight: 200,
            margin: 12,
            float: true,
        },
        gridContainer.value,
    );

    grid.on('change', (event, items) => {
        console.log('Новые позиции для сохранения в БД:', items);
    });
});
</script>

<template>
    <Head title="Дашборд метрик" />

    <DashboardLayout>
        <div class="p-4">
            <h2 class="title">Дашборды / Продажи</h2>

            <section class="mt-4">
                <div class="grid-stack min-h-[200px]" ref="gridContainer">
                    <div
                        v-for="w in widgets"
                        :key="w.id"
                        :gs-id="w.id"
                        :gs-x="w.x"
                        :gs-y="w.y"
                        :gs-w="w.w"
                        :gs-h="w.h"
                        class="grid-stack-item"
                    >
                        <div class="grid-stack-item-content content-glass p-4">
                            {{ w.content }}
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>