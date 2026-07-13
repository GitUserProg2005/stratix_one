<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    backgrounds: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: [Number, null],
        default: null,
    },
});

const selectedBackgroundId = ref(props.modelValue);
const isSaving = ref(false);

watch(
    () => props.modelValue,
    (value) => {
        selectedBackgroundId.value = value;
    },
);

async function selectBackground(id) {
    if (isSaving.value || selectedBackgroundId.value === id) {
        return;
    }

    const previousId = selectedBackgroundId.value;
    selectedBackgroundId.value = id;
    isSaving.value = true;

    try {
        await axios.post(route('profile.background'), {
            background_id: id,
        });
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('Ошибка сохранения фона интерфейса:', error);
        selectedBackgroundId.value = previousId;
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <section class="mt-6">
        <h2 class="title-2 mb-3 flex items-center gap-2 text-sm">
            Фон интерфейса
        </h2>

        <p v-if="isSaving" class="context text-xs mb-3 t-color-primary">Сохранение...</p>

        <div
            v-if="backgrounds.length"
            class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
        >
            <button
                v-for="background in backgrounds"
                :key="background.id"
                type="button"
                class="group relative aspect-video overflow-hidden rounded-2xl border-2 transition"
                :class="selectedBackgroundId === background.id
                    ? 'border-[var(--accent)] ring-2 ring-[var(--accent)]/40'
                    : 'border-transparent hover:border-white/20'"
                @click="selectBackground(background.id)"
            >
                <img
                    v-if="background.picture_url"
                    :src="background.picture_url"
                    :alt="background.title"
                    class="size-full object-cover"
                />
                <div
                    v-else
                    class="flex size-full items-center justify-center bg-content-glass text-xs context"
                >
                    Нет изображения
                </div>

                <div class="absolute inset-x-0 bottom-0 bg-black/55 px-2 py-1.5 text-left">
                    <span class="t-mini truncate text-white">{{ background.title }}</span>
                </div>
            </button>
        </div>

        <p v-else class="context text-sm opacity-70">
            Пока нет доступных фонов.
        </p>
    </section>
</template>
