<script setup>
import { computed } from 'vue';
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean, Object, Array],
        default: null,
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Выберите значение',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    buttonClass: {
        type: String,
        default: 'select-input w-full',
    },
    optionsClass: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const selectedOption = computed(() => {
    return props.options.find((option) => option.value === props.modelValue) ?? null;
});

const selectedLabel = computed(() => {
    return selectedOption.value?.label ?? props.placeholder;
});
</script>

<template>
    <Listbox :model-value="modelValue" :disabled="disabled" @update:model-value="(value) => emit('update:modelValue', value)">
        <div class="relative">
            <ListboxButton :class="[buttonClass, 'flex items-center justify-between gap-2 text-left disabled:opacity-60 disabled:cursor-not-allowed']">
                <span class="truncate">
                    {{ selectedLabel }}
                </span>
                <span class="pointer-events-none text-xs opacity-70">▼</span>
            </ListboxButton>

            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <ListboxOptions
                    class="absolute z-30 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-[var(--border-input)] bg-[var(--bg-main)] py-1 shadow-lg focus:outline-none"
                    :class="optionsClass"
                >
                    <ListboxOption v-for="option in options" :key="`opt-${String(option.value)}`" v-slot="{ active, selected }" :value="option.value" as="template">
                        <li
                            class="cursor-pointer select-none px-3 py-2 text-sm"
                            :class="[
                                active ? 'bg-[rgba(233,115,88,0.16)]' : '',
                                selected ? 'font-semibold text-[var(--accent)]' : 'text-[var(--content-primary)]',
                            ]"
                        >
                            {{ option.label }}
                        </li>
                    </ListboxOption>
                </ListboxOptions>
            </transition>
        </div>
    </Listbox>
</template>
