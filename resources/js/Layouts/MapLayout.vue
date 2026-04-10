<script setup>
import '../../css/custom.css';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Avatar from '@/Components/Avatar.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const isOpenSidebar = ref(false);
const isMobileView = ref(true);
const headerRef = ref(null);
const headerStatic = ref(false);
const VAR_HEADER_HEIGHT = '--app-header-height';
const MOBILE_MAX_WIDTH = 992;

function updateMobileView() {
  isMobileView.value = window.matchMedia(`(max-width: ${MOBILE_MAX_WIDTH}px)`).matches;
}
function updateHeaderHeightVar() {
  if (!headerRef.value) return;
  const root = document.documentElement;
  const isFixedHeader = !headerStatic.value && !window.matchMedia('(min-width: 1024px)').matches;
  root.style.setProperty(VAR_HEADER_HEIGHT, isFixedHeader ? `${headerRef.value.offsetHeight}px` : '0px');
}

onMounted(() => {
  updateMobileView();
  updateHeaderHeightVar();
  window.addEventListener('resize', updateMobileView);
  window.addEventListener('resize', updateHeaderHeightVar);
});
onBeforeUnmount(() => {
  window.removeEventListener('resize', updateMobileView);
  window.removeEventListener('resize', updateHeaderHeightVar);
  document.documentElement.style.removeProperty(VAR_HEADER_HEIGHT);
});
</script>

<template>
  <div class="max-w-7xl mx-auto relative bg-body">
    <div v-show="isMobileView && isOpenSidebar" class="fixed inset-0 z-40 backdrop-blur-md bg-black/30 dark:bg-black/60 transition-opacity duration-300 lg:hidden" @click="isOpenSidebar = false" />

    <header v-show="isMobileView" ref="headerRef" class="fixed top-0 left-0 right-0 z-20 lg:static">
      <div class="flex items-center justify-between px-4 py-3 border-b border-black/10 dark:border-white/10">
        <span class="title"><img src="/img/drivee_logo.png" class="object-contain w-12" alt=""></span>
        <button type="button" class="lg:hidden w-10 h-10 rounded-full bg-content-outline flex items-center justify-center transition-colors hover:opacity-80" @click="isOpenSidebar = !isOpenSidebar">
          <i class="fa-solid fa-bars text-md"></i>
        </button>
      </div>
    </header>

    <div v-show="isMobileView" class="fixed left-0 inset-y-0 w-[85%] max-w-sm bg-body shadow-xl transform transition-transform duration-300 z-50 flex flex-col lg:hidden border-r border-black/10 dark:border-white/10" :class="{ 'translate-x-0': isOpenSidebar, '-translate-x-full': !isOpenSidebar }">
      <div class="flex items-center justify-between px-4 py-3 border-b border-black/10 dark:border-white/10">
        <span class="context font-medium">Меню</span>
        <button type="button" class="context hover:opacity-80" @click="isOpenSidebar = false">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>
      <div class="flex items-center gap-3 px-4 py-4 border-b border-black/10 dark:border-white/10">
        <Avatar v-if="user" :name="user.name" :src="user.avatar_url" :user-id="user.id" no-link class="shrink-0" />
        <div class="min-w-0 flex-1">
          <span class="t-body font-semibold truncate block">{{ user ? user.name : 'Гость' }}</span>
        </div>
      </div>
    </div>

    <div v-show="isMobileView" class="mx-auto h-screen overflow-hidden grid grid-cols-1 grid-rows-[1fr] lg:grid-cols-[400px_1fr] lg:grid-rows-[auto_1fr]">
      <slot />
    </div>
  </div>
</template>
