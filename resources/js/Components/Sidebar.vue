<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Avatar from './Avatar.vue';
import Playlists from '@/Pages/Playlist/Playlists.vue';
import Notifications from './Notifications.vue';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);

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
        class="fixed inset-0 z-[100] bg-black/40 lg:hidden"
        @click="emit('update:isOpenSidebar', false)"
    />

    <!-- Sidebar -->
    <aside
      class="
        fixed inset-y-0 left-0
        transform transition-transform duration-300
        lg:translate-x-0 lg:relative 
        z-[100]
        lg:z-[50]
        bg-transparent
        backdrop-blur-2xl
        lg:bg-body
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

        <div class="flex justify-between items-center 
        w-full px-4
        ">
          <img src="/img/wix_logo4.png" class="hidden lg:flex w-8 object-contain" alt="">

          <div class="flex items-center gap-4 font-semibold bg-content pl-3 rounded-full">
            <Notifications v-if="currentUser" />
            <i v-else class="fa-regular fa-bell"></i>
            <Avatar 
              v-if="currentUser" 
              :name="currentUser.name" 
              :src="currentUser.avatar_url"
              :userId="currentUser.id" 
            />
            <Avatar v-else name="Guest" />
          </div>
        </div>

        <div class="border-b border-gray-500
        w-full pb-4 mb-4"></div>

        <!-- Премиум -->
        <div class="px-4 mb-4">
          <div class="bg-content p-4 rounded-2xl w-full space-y-2">
            <h3 class="title-2">Приобрести премиум</h3>
            <p class="context">
              Без рекламы и высокое качество звука
            </p>
            <button
              type="button"
              class="primary-btn mt-2 inline-flex items-center gap-2"
            >
              Оформить подписку
              <i class="fa-solid fa-crown text-xs" />
            </button>
          </div>
        </div>

        <Playlists />
      </div>
    </aside>
</template>