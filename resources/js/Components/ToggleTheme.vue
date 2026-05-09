<script setup>
import { computed, onMounted, ref } from 'vue';

const STORAGE_KEY = 'stratix-theme';

const isDark = ref(false);

function readStoredTheme() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'dark' || stored === 'light') {
        return stored;
    }
    if (typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    }
    return 'light';
}

function applyToDocument(theme) {
    const root = document.documentElement;
    if (theme === 'dark') {
        root.classList.add('dark');
    } else {
        root.classList.remove('dark');
    }
    isDark.value = theme === 'dark';
}

onMounted(() => {
    applyToDocument(readStoredTheme());
});

function toggle() {
    const next = isDark.value ? 'light' : 'dark';
    localStorage.setItem(STORAGE_KEY, next);
    applyToDocument(next);
}

const label = computed(() => (isDark.value ? 'Светлая тема' : 'Тёмная тема'));
</script>

<template>
    <!-- UI убран: компонент только применяет сохранённую тему при загрузке -->
    <span class="sr-only" aria-hidden="true"></span>
</template>
