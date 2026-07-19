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
    <div class="relative h-full" @contextmenu="toggleOpen">
        <!--Контент-->
        <slot />

        <!--Мини-модалка-->
        <div
            v-if="isOpen"
            ref="menuRef"
            @click.stop
            class="absolute top-2 left-10 z-50 backdrop-blur-xl bg-content-outline px-4 py-2 rounded-lg"
        >
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-pencil"></i>
                <slot name="menu" :toggleOpen="toggleOpen" />
            </div>
        </div>
    </div>
</template>