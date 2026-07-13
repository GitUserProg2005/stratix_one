<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CreateDashboard from './CreateDashboard.vue';
import NoAccess from '@/Components/NoAccess.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    dashboards: {
        type: Array,
        default: () => [],
    },
    has_access: {
        type: Boolean,
        default: false,
    },
    access_error: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <Head title="Дашборды" />

    <DashboardLayout>
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="title">Метрики / Отчеты</h2>
                <CreateDashboard v-if="has_access" />
            </div>

            <div v-if="has_access" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <Link
                    v-for="d in dashboards"
                    :key="d.id"
                    :href="route('dashboard.show', d.id)"
                    class="dashboard-card transition"
                >
                    <div class="context font-semibold truncate">
                        {{ d.title || `Дашборд #${d.id}` }}
                    </div>

                    <div class="context text-xs opacity-70 mt-1 truncate">
                        <span v-if="d.workflow">Workflow: {{ d.workflow.name }}</span>
                        <span v-else>Без workflow</span>
                    </div>
                </Link>

                <div v-if="!dashboards.length" class="context opacity-70">
                    Дашбордов пока нет
                </div>
            </div>
            <NoAccess v-else-if="access_error" :title="access_error" />
        </div>
    </DashboardLayout>
</template>