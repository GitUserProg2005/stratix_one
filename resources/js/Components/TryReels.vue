<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <div class="w-full p-6 rounded-2xl bg-gradient-to-b from-[#1F1F1F] to-[#0f0f0f] text-white">
      <div class="flex flex-col md:flex-row items-center justify-between gap-6">
        
        <!-- Текстовая часть -->
        <div class="flex flex-col gap-4 max-w-lg">
          <h2 class="text-3xl font-extrabold leading-snug">
            Попробуйте нашу ленту <span class="text-yellow-400 font-black">Wix!</span>
          </h2>
          <p class="text-lg text-white/80">
            Откройте для себя свежие треки, подборки и вдохновение от других пользователей.
          </p>
          <div class="flex gap-4 mt-2">
            <button class="primary-btn">
              Перейти в ленту
            </button>
          </div>
        </div>

        <!-- Картинка -->
        <img src="/img/banner.png" class="w-full max-w-[500px]" alt="Wix banner" />
      </div>
    </div>
</template>
