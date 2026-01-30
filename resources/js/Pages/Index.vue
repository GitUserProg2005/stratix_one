<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue'

import { ref } from 'vue'
</script>

<template>
  <AppLayout>
    <div class="flex flex-col items-center justify-center h-96">
      <h2 class="title">Добро пожаловать, Дмитрий!</h2>
      <button class="primary-btn mt-4 flex items-center gap-2">Приступить к работе <i class="fa-solid fa-arrow-right-long"></i></button>
    </div>
  </AppLayout>
</template>
