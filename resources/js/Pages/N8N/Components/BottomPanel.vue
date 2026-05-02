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

const panelHeight = computed(() =>
    props.component?.bottomPanel?.height || '400px'
);
</script>

<template>
    <div
        v-if="isOpened"
        class="absolute z-50 bottom-0 left-0 right-0 bg-content-glass w-full overflow-hidden"
        :style="{ height: panelHeight }"
    >
        <div class="flex h-full min-h-0 flex-col">
        <div class="flex shrink-0 items-center px-4 py-2">

            <h2 class="flex-1 min-w-0 truncate">
                {{ title }}
            </h2>

            <button class="shrink-0 ml-3" @click="emit('close')">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <div class="flex-1 min-h-0 overflow-hidden">
            <component
                :is="props.component"
                v-bind="props.componentProps"
                class="h-full min-h-0"
            />
        </div>
        </div>

    </div>
</template>