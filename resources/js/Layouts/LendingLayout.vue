<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import '../../css/custom.css'

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
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <!-- Mobile overlay -->
  <div
    v-if="isOpen"
    class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-2xl lg:hidden"
    @click="close"
  />

  <!-- Mobile Sidebar (slides in from the left) -->
  <aside
    class="
      fixed inset-y-0 left-0 z-[70]
      w-4/5 sm:w-72
      transform transition-transform duration-300
      bg-white/80 backdrop-blur-2xl border-r
      pt-4
      lg:hidden
    "
    :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="px-4 flex items-center justify-between mb-4">
      <h1 class="title-font-2 text-2xl">STRATIX</h1>

      <button class="p-2" @click="close" aria-label="Close menu">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <nav class="px-4 space-y-2">
      <a class="t-body block text-gray-700 hover:text-black transition-colors" href="#" @click="close">
        Главная
      </a>
      <a class="t-body block text-gray-700 hover:text-black transition-colors" href="#" @click="close">
        Компоненты
      </a>
      <a class="t-body block text-gray-700 hover:text-black transition-colors" href="#" @click="close">
        Оплата
      </a>
      <a class="t-body block text-gray-700 hover:text-black transition-colors" href="#" @click="close">
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
      class="sticky top-0 z-50 w-full transition-colors duration-200"
      :class="isScrolled ? 'bg-white/20 backdrop-blur-xl shadow-sm' : 'bg-transparent'"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-10">
            <h1 class="title-font">STRATIX</h1>

            <nav class="hidden lg:flex items-center gap-12 ml-10">
              <a class="t-body text-gray-700 hover:text-black transition-colors" href="#">Главная</a>
              <a class="t-body text-gray-700 hover:text-black transition-colors" href="#">Компоненты</a>
              <a class="t-body text-gray-700 hover:text-black transition-colors" href="#">Оплата</a>
              <a class="t-body text-gray-700 hover:text-black transition-colors" href="#">Counter</a>
            </nav>
          </div>

          <div class="flex items-center gap-3">
            <div class="hidden lg:flex items-center gap-3">
              <button class="primary-btn">Войти</button>
              <button class="primary-btn-white-blur">Регистрация</button>
            </div>

            <!-- Burger (two lines) -->
            <button
              class="lg:hidden p-2"
              @click="isOpen = true"
              aria-label="Open menu"
            >
              <span class="block w-7 h-[2px] bg-black"></span>
              <span class="block w-7 h-[2px] bg-black mt-1"></span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-40">
      <slot />
    </main>
  </div>
</template>

