<script setup>
import { ref, computed, watch } from 'vue';

import HeadlessSelect from '@/Components/HeadlessSelect.vue';

const props = defineProps({
    projects: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['update:filteredProjects']);

const selectedStatus = ref(null);

const statusOptions = [
    { value: null, label: 'Все статусы' },
    { value: 'started', label: 'Начатые' },
    { value: 'in_progress', label: 'В процессе' },
    { value: 'completed', label: 'Завершён' },
];

function projectStatus(project) {
    return typeof project.status === 'object' ? project.status?.value : project.status;
}

const filteredProjects = computed(() => {
    // Без фильтра — все проекты
    if (!selectedStatus.value) {
        return props.projects;
    }

    return props.projects.filter((project) => {
        return projectStatus(project) === selectedStatus.value;
    });
});

// Отдаём отфильтрованный список наружу
watch(
    filteredProjects,
    (list) => {
        emit('update:filteredProjects', list);
    },
    { immediate: true },
);
</script>

<template>
    <HeadlessSelect
        v-model="selectedStatus"
        :options="statusOptions"
        placeholder="Выберите статус"
        class="w-48"
    />
</template>
