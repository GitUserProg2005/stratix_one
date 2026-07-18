<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    prefix: {
        type: String,
        default: null,
    },
});

const prefix = ref(props.prefix);
const plainKey = ref(null);
const loading = ref(false);
const copied = ref(false);
const error = ref(null);

watch(() => props.prefix, (value) => {
    prefix.value = value;
});

async function regenerate() {
    loading.value = true;
    error.value = null;
    copied.value = false;

    try {
        const { data } = await axios.post(route('profile.api-key.regenerate'));
        plainKey.value = data.api_key;
        prefix.value = data.prefix_api_key;
    } catch (e) {
        error.value = 'Не удалось сгенерировать ключ';
        console.error(e);
    } finally {
        loading.value = false;
    }
}

async function copyKey() {
    if (!plainKey.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(plainKey.value);
        copied.value = true;
    } catch (e) {
        console.error(e);
    }
}
</script>

<template>
    <div class="min-w-0 mt-4">
        <div class="flex items-center gap-2">
            <p class="context text-sm break-all font-mono">
                <template v-if="plainKey">{{ plainKey }}</template>
                <template v-else-if="prefix">{{ prefix }}…</template>
                <template v-else>Ключ не создан</template>
            </p>

            <button
                v-if="plainKey"
                type="button"
                class="primary-btn-blur shrink-0 px-2 py-1 text-xs"
                :title="copied ? 'Скопировано' : 'Копировать'"
                @click="copyKey"
            >
                <i :class="copied ? 'fa-solid fa-check' : 'fa-regular fa-copy'" />
            </button>

            <button
                type="button"
                class="primary-btn-blur shrink-0 px-2 py-1 text-xs"
                :disabled="loading"
                :title="prefix ? 'Регенерировать' : 'Сгенерировать'"
                @click="regenerate"
            >
                <i :class="loading ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-rotate'" />
            </button>
        </div>

        <p
            v-if="plainKey"
            class="context text-xs mt-4 opacity-70"
        >
            Скопируйте ключ сейчас — повторно он не покажется
        </p>

        <p
            v-if="error"
            class="context text-xs mt-1 t-color-danger"
        >
            {{ error }}
        </p>
    </div>
</template>
