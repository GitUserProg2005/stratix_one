<script setup>
import Avatar from './Avatar.vue';
import Playlists from '@/Pages/Playlist/Playlists.vue';

const props = defineProps({
    isOpenSidebar: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['update:isOpenSidebar']);
</script>


<template>
    <div 
        v-if="isOpenSidebar"
        class="fixed inset-0 z-40 bg-black/40 lg:hidden"
        @click="emit('update:isOpenSidebar', false)"
    />

    <!-- Sidebar -->
    <aside
      class="
        fixed inset-y-0 left-0
        transform transition-transform duration-300
        lg:translate-x-0 lg:relative 
        border-r border-gray-500
        z-50
        bg-body
        pt-4
        w-4/5 sm:w-72 lg:w-96
      "
      :class="{ 'translate-x-0': isOpenSidebar, '-translate-x-full': !isOpenSidebar }"
    >
      <div class="flex flex-col w-full">
        <div class="flex items-center justify-between px-4 w-full mb-4 lg:hidden">
            <h2 class="title">Мой профиль</h2>

            <button
            class="lg:hidden"
            @click="emit('update:isOpenSidebar', false)"
            >
            <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="flex flex-wrap justify-between items-center 
        w-full px-4
        ">
          <div class="flex items-center font-semibold">
            <Avatar name="Gony" />
          </div>
        </div>

        <div class="border-b border-gray-500
        w-full pb-4 mb-4"></div>

        <Playlists />
      </div>
    </aside>
</template>