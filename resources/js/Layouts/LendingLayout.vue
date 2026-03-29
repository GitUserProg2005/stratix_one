<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import '../../css/custom.css';

import Cursor from '@/Components/Cursor.vue';

const isOpen = ref(false)
const isScrolled = ref(false)

const close = () => {
  isOpen.value = false
}

const handleScroll = () => {
  isScrolled.value = window.scrollY > 10
}

onMounted(() => {
  handleScroll()
  window.addEventListener('scroll', handleScroll, { passive: true })

  document.body.style.cursor = 'none';
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)
})
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
      <h1 class="title-font-2-on-dark text-2xl">STRATIX</h1>

      <button type="button" class="lending-drawer-close" @click="close" aria-label="Закрыть меню">
        <i class="fa-solid fa-xmark text-lg" aria-hidden="true" />
      </button>
    </div>

    <nav class="px-4 space-y-1" aria-label="Мобильное меню">
      <a class="lending-nav-link lending-nav-link--block" href="#" @click.prevent="close">
        Главная
      </a>
      <a class="lending-nav-link lending-nav-link--block" href="#" @click.prevent="close">
        Компоненты
      </a>
      <a class="lending-nav-link lending-nav-link--block" href="#" @click.prevent="close">
        Оплата
      </a>
      <a class="lending-nav-link lending-nav-link--block" href="#" @click.prevent="close">
        Counter
      </a>
    </nav>

    <div class="px-4 mt-6 space-y-3">
      <button class="primary-btn w-full" @click="close">Войти</button>
      <button class="tag w-full" @click="close">Регистрация</button>
    </div>
  </aside>

  <!-- Desktop wrapper + header + page content -->
  <div class="relative z-10">
    <header
      class="sticky top-0 z-50 w-full transition-colors duration-200 rounded-b-3xl mx-auto"
      :class="isScrolled ? 'backdrop-blur-xl shadow-sm' : 'bg-transparent'"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 md:py-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-10">
            <h1 class="title-font">STRATIX</h1>

            <nav class="hidden lg:flex items-center gap-12 ml-10" aria-label="Основное меню">
              <a class="lending-nav-link text-base font-medium" href="#">Главная</a>
              <a class="lending-nav-link text-base font-medium" href="#">Компоненты</a>
              <a class="lending-nav-link text-base font-medium" href="#">Оплата</a>
              <a class="lending-nav-link text-base font-medium" href="#">Counter</a>
            </nav>
          </div>

          <div class="flex items-center gap-3">
            <div class="hidden lg:flex items-center gap-3">
              <button class="primary-btn">Войти</button>
              <button class="primary-btn-white-blur">Регистрация</button>
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 md:py-8 lg:py-10">
      <slot />
    </main>
  </div>
</template>

