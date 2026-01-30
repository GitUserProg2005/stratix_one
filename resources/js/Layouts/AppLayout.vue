<script setup>
import '../../css/custom.css';

import Avatar from '@/Components/Avatar.vue'
import Sidebar from '@/Components/Sidebar.vue';

import { ref } from 'vue'

const isOpenSidebar = ref(false)
</script>

<template>
  <div class="h-screen flex overflow-hidden relative">
    <Sidebar :is-open-sidebar="isOpenSidebar" @update:is-open-sidebar="isOpenSidebar = $event" />

    <!-- Main -->
    <main class="flex-1 overflow-y-auto">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="lg:hidden mb-4">
                <button @click="isOpenSidebar = true">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>

            <slot />
        </div>
    </main>
  </div>
</template>
