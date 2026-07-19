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
    return props.options.find((option) => String(option.value) === String(props.modelValue)) ?? null;
});

const selectedLabel = computed(() => {
    return selectedOption.value?.label ?? props.placeholder;
});

const selectedToneClass = computed(() => {
    const tone = selectedOption.value?.tone;

    if (tone === 'file') {
        return 'text-sky-500';
    }

    return '';
});
</script>

<template>
    <Listbox :model-value="modelValue" :disabled="disabled" @update:model-value="(value) => emit('update:modelValue', value)">
        <div class="relative">
            <ListboxButton :class="[buttonClass, 'flex items-center justify-between dashboard-inset gap-2 text-left disabled:opacity-60 disabled:cursor-not-allowed']">
                <span class="flex min-w-0 items-center gap-1.5 truncate" :class="selectedToneClass">
                    <i
                        v-if="selectedOption?.icon"
                        :class="selectedOption.icon"
                        class="shrink-0 text-xs"
                        aria-hidden="true"
                    />
                    <span class="truncate">
                        {{ selectedLabel }}
                    </span>
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
                    class="absolute z-30 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-content-glass no-scrollbar py-2 shadow-lg focus:outline-none"
                    :class="optionsClass"
                >
                    <ListboxOption v-for="option in options" :key="`opt-${String(option.value)}`" v-slot="{ active, selected }" :value="option.value" as="template">
                        <li
                            class="flex cursor-pointer select-none items-center gap-1.5 px-3 py-2 text-sm"
                            :class="[
                                active ? 'bg-[rgba(233, 115, 88,0.16)]' : '',
                                selected ? 'font-semibold text-[var(--accent)]' : 'text-[var(--content-primary)]',
                                option.tone === 'file' ? 'text-sky-500' : '',
                            ]"
                        >
                            <i
                                v-if="option.icon"
                                :class="option.icon"
                                class="shrink-0 text-xs"
                                aria-hidden="true"
                            />
                            <span>{{ option.label }}</span>
                        </li>
                    </ListboxOption>
                </ListboxOptions>
            </transition>
        </div>
    </Listbox>
</template>
