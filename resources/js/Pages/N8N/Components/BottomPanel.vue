<script setup>
import { computed } from 'vue';

const props = defineProps({
    component: Object,
    componentProps: Object,
});

const emit = defineEmits(['close']);

const isOpened = computed(() => !!props.component);

const title = computed(() =>
    props.component?.bottomPanel?.name || 'Что-то пошло не так'
);
</script>

<template>
    <div v-if="isOpened" class="absolute z-50 bottom-0 left-0 right-0 bg-body w-full h-[400px] overflow-hidden">
        <div class="flex items-center px-4 py-2">

            <h2 class="flex-1 min-w-0 truncate">
                {{ title }}
            </h2>

            <button class="shrink-0 ml-3" @click="emit('close')">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <div class="h-full overflow-auto">
            <component
                :is="props.component"
                v-bind="props.componentProps"
            />
        </div>

    </div>
</template>