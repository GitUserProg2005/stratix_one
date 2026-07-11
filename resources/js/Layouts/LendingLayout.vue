<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import '../../css/custom.css';

import Avatar from '@/Components/Avatar.vue';
import Cursor from '@/Components/Cursor.vue';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);

const navLinks = [
    { label: 'О продукте', href: '#' },
    { label: 'Почему мы', href: '#' },
    { label: 'Тарифы', href: '#' },
    { label: 'Документация', href: '#' },
];

const isOpen = ref(false);
const isScrolled = ref(false);

const close = () => {
    isOpen.value = false;
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 10;
};

onMounted(() => {
    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });

    document.body.style.cursor = 'none';
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <Cursor />

  <!-- Тёплое свечение #feeccd сверху справа, очень мягко к низу -->
  <div class="lending-layout-glow" aria-hidden="true" />

  <!-- Mobile overlay -->
  <div
    v-if="isOpen"
    class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-2xl lg:hidden"
    @click="close"
  />

  <!-- Mobile Sidebar (slides in from the left) -->
  <aside
    class="
      lending-mobile-drawer
      fixed inset-y-0 left-0 z-[70]
      w-4/5 sm:w-72
      transform transition-transform duration-300
      bg-content-dark backdrop-blur-2xl border-r border-white/10
      pt-4
      lg:hidden
    "
    :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="px-4 flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <h1 class="title-font-3-on-dark">STRATIX</h1>
        <img src="/img/new_logo.png" alt="STRATIX" class="object-contain w-12" />
        <h1 class="title-font-3-on-dark">ID</h1>
      </div>

      <button type="button" class="lending-drawer-close" @click="close" aria-label="Закрыть меню">
        <i class="fa-solid fa-xmark text-lg" aria-hidden="true" />
      </button>
    </div>

    <nav class="px-4 space-y-1" aria-label="Мобильное меню">
      <a
        v-for="link in navLinks"
        :key="link.label"
        class="lending-nav-link lending-nav-link--block text-base font-medium"
        :href="link.href"
        @click.prevent="close"
      >
        {{ link.label }}
      </a>
    </nav>

    <div class="px-4 mt-6">
      <Link
        v-if="currentUser"
        :href="route('profile.edit')"
        class="flex items-center gap-3 w-full"
        @click="close"
      >
        <Avatar
          :name="currentUser.name"
          :src="currentUser.avatar_url"
          :user-id="currentUser.id"
          no-link
          size="md"
        />
        <span class="lending-nav-link text-base font-semibold truncate">
          {{ currentUser.name }}
        </span>
      </Link>

      <div v-else class="space-y-3">
        <Link :href="route('register')" class="primary-btn-white-blur w-full inline-flex items-center justify-center" @click="close">
          <i class="fa-regular fa-user mr-2" aria-hidden="true" />
          Регистрация
        </Link>
        <Link :href="route('login')" class="primary-btn w-full inline-flex items-center justify-center" @click="close">
          Вход
        </Link>
      </div>
    </div>
  </aside>

  <!-- Desktop wrapper + header + page content -->
  <div class="relative z-10">
    <div class="auth-grid-bg">
      <header
        class="sticky top-0 z-50 w-full transition-colors duration-200 rounded-b-3xl mx-auto"
        :class="isScrolled ? 'backdrop-blur-xl shadow-sm' : 'bg-transparent'"
      >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 md:py-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-10">
              <div class="flex items-center gap-3">
                <h1 class="title-font-3">STRATIX</h1>
                <img src="/img/new_logo.png" alt="STRATIX" class="object-contain w-12" />
                <h1 class="title-font-3">ID</h1>
              </div>

              <nav class="hidden lg:flex items-center gap-12 ml-10" aria-label="Основное меню">
                <a
                  v-for="link in navLinks"
                  :key="link.label"
                  class="lending-nav-link text-base font-medium"
                  :href="link.href"
                >
                  {{ link.label }}
                </a>
              </nav>
            </div>

            <div class="flex items-center gap-3">
              <div class="hidden lg:flex items-center gap-3">
                <Link
                  v-if="currentUser"
                  :href="route('profile.edit')"
                  class="flex items-center gap-3 min-w-0 bg-content-glass px-2 py-2 rounded-full"
                >
                  <Avatar
                    :name="currentUser.name"
                    :src="currentUser.avatar_url"
                    :user-id="currentUser.id"
                    no-link
                    size="md"
                  />
                  <span class="font-semibold truncate max-w-[10rem]">
                    {{ currentUser.name }}
                  </span>
                </Link>

                <template v-else>
                  <Link :href="route('register')" class="primary-btn-white-blur inline-flex items-center">
                    <i class="fa-regular fa-user mr-2" aria-hidden="true" />
                    Регистрация
                  </Link>
                  <Link :href="route('login')" class="primary-btn">
                    Вход
                  </Link>
                </template>
              </div>

              <!-- Burger (two lines) -->
              <button
                type="button"
                class="lending-header-icon-btn lg:hidden p-2 rounded-xl transition-colors"
                @click="isOpen = true"
                aria-label="Открыть меню"
              >
                <span class="lending-burger-line" />
                <span class="lending-burger-line" />
              </button>
            </div>
          </div>
        </div>
      </header>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <slot name="hero" />
      </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 md:py-8 lg:py-10">
      <slot />
    </main>
  </div>
</template>
