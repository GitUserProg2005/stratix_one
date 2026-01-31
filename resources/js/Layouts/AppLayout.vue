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
        <div class="lg:hidden title p-4">
            <div class="flex items-center justify-between">
                <!--Лого-->
                <img src="" alt="">

                <!--Sidebar-->
                <button @click="isOpenSidebar = true">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        <div class="w-full mx-auto">
            <slot />
        </div>
    </main>
  </div>
</template>
