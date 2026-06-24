<script setup>
import { ref } from 'vue';

defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const isVisible = ref(false);

function toggleVisibility() {
    isVisible.value = !isVisible.value;
}
</script>

<template>
    <div class="relative mt-2">
        <input
            :type="isVisible ? 'text' : 'password'"
            :value="modelValue"
            class="input w-full pr-10"
            :placeholder="placeholder"
            autocomplete="off"
            @input="emit('update:modelValue', $event.target.value)"
        />

        <button
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 border-0 bg-transparent p-1 text-inherit opacity-70 transition-opacity hover:opacity-100"
            :aria-label="isVisible ? 'Скрыть значение' : 'Показать значение'"
            @click="toggleVisibility"
        >
            <i :class="isVisible ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" />
        </button>
    </div>
</template>
