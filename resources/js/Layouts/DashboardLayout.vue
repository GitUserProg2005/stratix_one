<script setup>
import '../../css/custom.css';

import Sidebar from '@/Components/Sidebar.vue';
import RightInfo from '@/Pages/RightInfo.vue';
import Search from '@/Components/Search/Search.vue';
import Avatar from '@/Components/Avatar.vue';

import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3';

const isOpenSidebar = ref(false)
const isMobileActionsOpen = ref(false)
const mobileActionsWrap = ref(null)

// Stub search for now: пока не подключаем серверный поиск
const searchFn = async () => []

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);
const username = computed(() => currentUser.value?.name ?? 'Username');

const onDocumentClick = (e) => {
  // Закрываем выпадашку при клике вне области.
  if (!isMobileActionsOpen.value) return
  const el = mobileActionsWrap.value
  if (!el) return
  if (el.contains(e.target)) return
  isMobileActionsOpen.value = false
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
})
</script>

<template>
  <div
    class="
      h-screen overflow-hidden
      grid grid-cols-1
      lg:grid-cols-[16rem_1fr]
      select-none
      gap-2
    "
  >
    <!-- Sidebar -->
    <Sidebar
      :is-open-sidebar="isOpenSidebar"
      @update:isOpenSidebar="isOpenSidebar = $event"
    />

    <!-- Main -->
    <main class="overflow-y-auto bg-content">
      <header class="sticky top-0 z-50 backdrop-blur-xl px-4">
        <div class="py-6">
          <div class="flex justify-between items-center gap-6 w-full">
            <!-- Mobile burger -->
            <button
              class="lg:hidden w-10 h-10 rounded-full bg-[#e97358]/10 text-[#e97358] flex flex-col p-3 items-center justify-center"
              @click="isOpenSidebar = true"
              aria-label="Open menu"
            >
              <span class="block w-5 h-[2px] bg-[#e97358]"></span>
              <span class="block w-5 h-[2px] bg-[#e97358] mt-1"></span>
            </button>

            <!-- Wide search -->
            <div class="flex-1 min-w-0 w-full">
              <Search :search-fn="searchFn" />
            </div>

            <!-- Notifications + user -->
            <div class="flex items-center gap-4 shrink-0">
              <div class="hidden lg:flex items-center gap-4">
                <button
                  class="relative w-10 h-10 rounded-full bg-[#e97358]/10 text-[#e97358] flex items-center justify-center"
                  aria-label="Notifications"
                >
                  <i class="fa-solid fa-bell text-sm" />
                  <span class="absolute top-2 right-2 w-2 h-2 bg-[#e97358] rounded-full" />
                </button>

                <button
                  class="w-10 h-10 rounded-full bg-[#e97358]/10 text-[#e97358] flex items-center justify-center"
                  aria-label="Messages"
                >
                  <i class="fa-solid fa-envelope text-sm" />
                </button>
              </div>

              <div
                ref="mobileActionsWrap"
                class="relative shrink-0 flex items-center gap-3"
              >
                <div
                  class="cursor-pointer"
                  role="button"
                  tabindex="0"
                  aria-label="Open user actions"
                  @click.stop="isMobileActionsOpen = !isMobileActionsOpen"
                  @keydown.enter.stop="isMobileActionsOpen = !isMobileActionsOpen"
                >
                  <Avatar
                    :name="username"
                    :src="currentUser?.avatar_url"
                    :userId="null"
                    :no-link="true"
                    size="md"
                  />
                </div>

                <div class="min-w-0">
                  <div class="text-xs font-semibold truncate">
                    Дмитрий
                  </div>
                  <div class="t-mini">
                    dmitriy@gmail.com
                  </div>
                </div>

                <!-- Mobile actions dropdown (under avatar) -->
                <div
                  v-if="isMobileActionsOpen"
                  class="lg:hidden absolute right-0 top-12 w-44 bg-white/80 backdrop-blur-xl border border-black/5 rounded-2xl shadow-2xl p-2 z-[60]"
                  @click.stop
                >
                  <button
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#e97358]/10 text-[#1a1a1a]"
                    aria-label="Notifications"
                    @click="isMobileActionsOpen = false"
                  >
                    <i class="fa-solid fa-bell text-sm text-[#e97358]" />
                    <span class="text-sm font-semibold">Notifications</span>
                  </button>

                  <button
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-[#e97358]/10 text-[#1a1a1a]"
                    aria-label="Messages"
                    @click="isMobileActionsOpen = false"
                  >
                    <i class="fa-solid fa-envelope text-sm text-[#e97358]" />
                    <span class="text-sm font-semibold">Messages</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <div class="w-full">
        <div class="space-y-6">
          <slot />
        </div>
      </div>
    </main>
  </div>
</template>

