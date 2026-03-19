<script setup>
import '../../css/custom.css';

import Sidebar from '@/Components/Sidebar.vue';
import RightInfo from '@/Pages/RightInfo.vue';
import Search from '@/Components/Search/Search.vue';
import Avatar from '@/Components/Avatar.vue';

import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3';

const isOpenSidebar = ref(false)

// Stub search for now: пока не подключаем серверный поиск
const searchFn = async () => []

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);
const username = computed(() => currentUser.value?.name ?? 'Username');
</script>

<template>
  <div
    class="
      h-screen overflow-hidden
      grid grid-cols-1
      lg:grid-cols-[16rem_1fr] lg:p-4 gap-4
    "
  >
    <!-- Sidebar -->
    <Sidebar
      :is-open-sidebar="isOpenSidebar"
      @update:isOpenSidebar="isOpenSidebar = $event"
    />

    <!-- Main -->
    <main class="overflow-y-auto">
      <div class="lg:hidden title p-4">
        <div class="flex items-center justify-between">
          <div> 
            <h2 class="title-font-2">STRATIX</h2>
          </div>

          <button
            class="lg:hidden p-2"
            @click="isOpenSidebar = true"
            aria-label="Open menu"
          >
            <span class="block w-7 h-[2px] bg-black"></span>
            <span class="block w-7 h-[2px] bg-black mt-1"></span>
          </button>
        </div>
      </div>

      <div class="w-full mx-auto">
        <div class="space-y-6 p-1">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
              <h2 class="title-2">Добро пожаловать, Дмитрий!</h2>
            </div>

            <div class="flex items-center gap-12">
              <Search :search-fn="searchFn" />

              <div class="shrink-0 flex items-center gap-3">
                <Avatar
                  :name="username"
                  :src="currentUser?.avatar_url"
                  :userId="null"
                  :no-link="true"
                  size="md"
                />

                <div class="hidden sm:block min-w-0">
                  <div class="text-sm font-semibold truncate">
                    Дмитрий
                  </div>
                  <div class="t-mini">
                    dima@gmail.com
                  </div>
                </div>
              </div>
            </div>
          </div>

          <slot />
        </div>
      </div>
    </main>
  </div>
</template>

