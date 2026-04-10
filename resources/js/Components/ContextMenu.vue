<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const isOpen = ref(false);
const menuRef = ref(null);

function toggleOpen(e) {
    e.preventDefault();
    isOpen.value = !isOpen.value;
}

function handleClickOutside(e) {
    if (!menuRef.value) return;

    if (!menuRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

function handleEscape(e) {
    if (e.key === 'Escape') {
        isOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscape);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <div class="relative" @contextmenu="toggleOpen">
        <!--Контент-->
        <slot />

        <!--Мини-модалка-->
        <div
            v-if="isOpen"
            ref="menuRef"
            @click.stop
            class="absolute top-2 left-2 z-50 content-outline"
        >
            <slot name="menu" :toggleOpen="toggleOpen" />
        </div>
    </div>
</template>