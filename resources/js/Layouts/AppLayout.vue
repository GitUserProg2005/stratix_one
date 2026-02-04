<script setup>
import '../../css/custom.css';

import Avatar from '@/Components/Avatar.vue'
import Sidebar from '@/Components/Sidebar.vue';
import RightInfo from '@/Pages/RightInfo.vue';

import { ref } from 'vue'

const isOpenSidebar = ref(false)
</script>

<template>
  <div
    class="
      h-screen overflow-hidden
      grid grid-cols-1
      lg:grid-cols-[24rem_1fr_18rem]
    "
  >
    <!-- Sidebar -->
    <Sidebar
      :is-open-sidebar="isOpenSidebar"
      @update:is-open-sidebar="isOpenSidebar = $event"
    />

    <!-- Main -->
    <main class="overflow-y-auto">
      <div class="lg:hidden title p-4">
        <div class="flex items-center justify-between">
          <img src="/img/wix_logo1.png" class="w-24 object-contain" />
          <button @click="isOpenSidebar = true">
            <i class="fa-solid fa-bars"></i>
          </button>
        </div>
      </div>

      <div class="w-full mx-auto">
        <slot />
      </div>
    </main>

    <!-- Right panel -->
    <RightInfo class="hidden lg:block" />
  </div>
</template>

