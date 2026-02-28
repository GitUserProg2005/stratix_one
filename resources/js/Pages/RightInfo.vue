<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import Avatar from '@/Components/Avatar.vue';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);
const friends = ref([]);
const friendsLoading = ref(false);

onMounted(async () => {
  if (!currentUser.value) return;
  friendsLoading.value = true;
  try {
    const { data } = await axios.get(route('friends.index'));
    friends.value = data;
  } catch {
    friends.value = [];
  } finally {
    friendsLoading.value = false;
  }
});
</script>

<template>
  <aside class="h-full flex flex-col bg-body p-4">
    <!-- Верхняя часть — пустое пространство -->
    <div class="flex-1 min-h-0" />

    <!-- Друзья (только для авторизованных) -->
    <div v-if="currentUser" class="mb-4 shrink-0">
      <h3 class="title-2 mb-3">Друзья 111</h3>
      <div v-if="friendsLoading" class="text-gray-400 text-sm">Загрузка...</div>
      <div v-else-if="friends.length === 0" class="text-gray-400 text-sm">
        Пока никого нет. Добавляйте друзей из профилей.
      </div>
      <ul v-else class="space-y-2 max-h-48 overflow-y-auto">
        <li v-for="friend in friends" :key="friend.id">
          <Link
            :href="route('user.profile', friend.id)"
            class="flex items-center gap-2 p-2 rounded-lg hover:bg-white/5 transition-colors"
          >
            <Avatar
              :name="friend.name"
              :src="friend.avatar_url"
              :userId="friend.id"
              no-link
            />
            <span class="truncate text-sm font-medium">{{ friend.name }}</span>
          </Link>
        </li>
      </ul>
    </div>

    <!-- Блок с картинкой, градиентом и CTA -->
    <div class="relative mt-auto flex flex-col items-center">
      <!-- Область картинки: градиент сверху (прозрачно → чёрный) накрывает изображение -->
      <div class="relative w-full flex justify-center overflow-hidden">
        <div
          class="absolute inset-0 z-10 pointer-events-none"
          style="background: linear-gradient(to bottom, transparent 0%, #0f0f0f 80%, #0f0f0f 70%);"
        />
        <img
          src="/img/phone.png"
          alt=""
          class="relative z-0 w-28 -mb-4 object-contain object-bottom"
        />
      </div>

      <!-- Текст и кнопка под «растворившейся» картинкой -->
      <div class="px-2 pb-2 pt-2 w-full space-y-4">
        <h3 class="title-2">Также можете попробовать приложение</h3>
        <p class="context">
          Слушай любимую музыку в приложении и наслаждайся высоким качеством звука
        </p>
        <button
          type="button"
          class="primary-btn mt-3 inline-flex items-center gap-2"
        >
          Установить приложение
          <i class="fa-solid fa-arrow-right text-xs" />
        </button>
      </div>
    </div>
  </aside>
</template>
