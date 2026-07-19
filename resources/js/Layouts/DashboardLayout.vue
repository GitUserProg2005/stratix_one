<script setup>
import '../../css/custom.css';

import Sidebar from '@/Components/Sidebar.vue';
import RightInfo from '@/Pages/RightInfo.vue';
import PointerEngine from '@/Components/PointerEngine.vue';
import Search from '@/Components/Search/Search.vue';
import Avatar from '@/Components/Avatar.vue';

import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
  fillHeight: {
    type: Boolean,
    default: false,
  },
});

const isOpenSidebar = ref(false)
const isMobileActionsOpen = ref(false)
const mobileActionsWrap = ref(null)

// Stub search for now: пока не подключаем серверный поиск
const searchFn = async () => []

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);
const username = computed(() => currentUser.value?.name ?? 'Username');
const interfaceBackgroundUrl = computed(() => page.props.background_url ?? null);

const dashboardBgStyle = computed(() => {
    if (!interfaceBackgroundUrl.value) {
        return {};
    }

    return {
        backgroundImage: `url('${interfaceBackgroundUrl.value}')`,
    };
});

const workflowId = computed(
    () => page.props.workflow?.id ?? page.props.dashboard?.workflow?.id ?? null,
);
const flowId = computed(() =>
    workflowId.value ? `workflow-${workflowId.value}` : null,
);
const showRightInfo = computed(() => Boolean(workflowId.value && flowId.value));

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
    class="dashboard-layout-bg h-screen min-h-0 overflow-hidden"
    :style="dashboardBgStyle"
  >
    <div
      class="
        relative z-10
        h-[calc(100%-1rem)] min-h-0 overflow-hidden
        grid grid-cols-1
        select-none
        gap-2
        m-2
      "
      :class="showRightInfo ? 'lg:grid-cols-[16rem_minmax(0,1fr)_16rem]' : 'lg:grid-cols-[16rem_minmax(0,1fr)]'"
    >
    <!-- Sidebar -->
    <Sidebar
      :is-open-sidebar="isOpenSidebar"
      @update:isOpenSidebar="isOpenSidebar = $event"
    />

    <!-- Main -->
    <main class="flex h-full min-h-0 flex-col overflow-hidden gap-2">
      <header class="shrink-0 pt-1">
        <div class="flex justify-between items-center gap-3 sm:gap-6 w-full">
            <!-- Mobile burger -->
            <button
              class="lg:hidden w-10 h-10 shrink-0 rounded-full bg-content-glass text-[#e97358] flex flex-col p-3 items-center justify-center"
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
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
              <button
                class="relative w-10 h-10 shrink-0 rounded-full bg-content-glass text-[#e97358] flex items-center justify-center"
                aria-label="Notifications"
              >
                <i class="fa-solid fa-bell text-sm" />
                <span class="absolute top-2 right-2 w-2 h-2 bg-[#e97358] rounded-full" />
              </button>

              <button
                class="w-10 h-10 shrink-0 rounded-full bg-content-glass text-[#e97358] flex items-center justify-center"
                aria-label="Messages"
              >
                <i class="fa-solid fa-envelope text-sm" />
              </button>

              <div
                ref="mobileActionsWrap"
                class="relative shrink-0"
              >
                <div class="bg-content-glass rounded-full flex items-center gap-1 sm:gap-2 pl-1 pr-1.5 sm:pr-2 py-1">
                  <Link
                    :href="route('profile.edit')"
                    class="flex items-center gap-2 sm:gap-3 min-w-0"
                    @click="isMobileActionsOpen = false"
                  >
                    <Avatar
                      :name="username"
                      :src="currentUser?.avatar_url"
                      :userId="currentUser?.id"
                      :no-link="true"
                      size="md"
                    />

                    <div class="min-w-0 hidden sm:block">
                      <div class="text-xs font-semibold truncate">
                        {{ username }}
                      </div>
                      <div class="t-mini truncate">
                        {{ currentUser?.email }}
                      </div>
                    </div>
                  </Link>

                  <button
                    type="button"
                    class="lg:hidden w-8 h-8 rounded-full text-[#e97358] flex items-center justify-center shrink-0"
                    aria-label="Open user actions"
                    @click.stop="isMobileActionsOpen = !isMobileActionsOpen"
                  >
                    <i class="fa-solid fa-chevron-down text-xs" />
                  </button>
                </div>

                <!-- Mobile actions dropdown (under avatar) -->
                <div
                  v-if="isMobileActionsOpen"
                  class="lg:hidden absolute right-0 top-12 w-44 bg-content-glass rounded-2xl shadow-2xl p-2 z-[60]"
                  @click.stop
                >
                  <button
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:opacity-80"
                    aria-label="Notifications"
                    @click="isMobileActionsOpen = false"
                  >
                    <i class="fa-solid fa-bell text-sm text-[#e97358]" />
                    <span class="text-sm font-semibold">Notifications</span>
                  </button>

                  <button
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:opacity-80"
                    aria-label="Messages"
                    @click="isMobileActionsOpen = false"
                  >
                    <i class="fa-solid fa-envelope text-sm text-[#e97358]" />
                    <span class="text-sm font-semibold">Messages</span>
                  </button>

                  <Link
                    :href="route('profile.edit')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:opacity-80"
                    @click="isMobileActionsOpen = false"
                  >
                    <i class="fa-solid fa-user text-sm text-[#e97358]" />
                    <span class="text-sm font-semibold">Профиль</span>
                  </Link>
                </div>
              </div>
            </div>
        </div>
      </header>

      <div
        class="min-h-0 flex-1 overflow-x-hidden no-scrollbar bg-content-glass rounded-2xl px-4 pb-4"
        :class="fillHeight ? 'overflow-hidden flex flex-col' : 'overflow-y-auto'"
      >
        <div :class="fillHeight ? 'min-h-0 flex-1 flex flex-col overflow-hidden' : 'space-y-6'">
          <slot />
        </div>
      </div>
    </main>

    <!-- Right sidebar -->
    <RightInfo
      v-if="showRightInfo"
      class="hidden lg:block min-h-0 h-full overflow-hidden rounded-2xl"
    />
    </div>
  </div>
</template>

