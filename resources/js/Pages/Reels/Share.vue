<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
  snippetId: { type: Number, required: true },
  snippetUrl: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const isOpen = ref(false);
const chats = ref([]);
const chatsLoading = ref(false);
const selectedChatIds = ref([]);
const shareToChatSubmitting = ref(false);

const shareOptions = [
  {
    id: 'copy',
    icon: 'fa-solid fa-link',
    label: 'Копировать ссылку',
    action: 'copy',
  },
  {
    id: 'telegram',
    icon: 'fa-brands fa-telegram',
    label: 'Telegram',
    action: 'telegram',
  },
  {
    id: 'whatsapp',
    icon: 'fa-brands fa-whatsapp',
    label: 'WhatsApp',
    action: 'whatsapp',
  },
  {
    id: 'vk',
    icon: 'fa-brands fa-vk',
    label: 'ВКонтакте',
    action: 'vk',
  },
];

async function loadChats() {
  chatsLoading.value = true;
  try {
    const { data } = await axios.get(route('chat.list'));
    chats.value = data;
  } catch {
    chats.value = [];
  } finally {
    chatsLoading.value = false;
  }
}

function toggleChat(chatId) {
  const idx = selectedChatIds.value.indexOf(chatId);
  if (idx === -1) {
    selectedChatIds.value = [...selectedChatIds.value, chatId];
  } else {
    selectedChatIds.value = selectedChatIds.value.filter((id) => id !== chatId);
  }
}

function isChatSelected(chatId) {
  return selectedChatIds.value.includes(chatId);
}

async function shareToChats() {
  if (selectedChatIds.value.length === 0 || shareToChatSubmitting.value) return;
  shareToChatSubmitting.value = true;
  try {
    await axios.post(route('share.snippet'), {
      chat_ids: selectedChatIds.value,
      snippet_id: props.snippetId,
    });
    selectedChatIds.value = [];
    close();
  } catch (err) {
    const msg = err.response?.data?.error || 'Не удалось отправить';
    alert(msg);
  } finally {
    shareToChatSubmitting.value = false;
  }
}

function open() {
  isOpen.value = true;
  loadChats();
}

function close() {
  isOpen.value = false;
  emit('close');
}

// Закрытие по клику на overlay
function handleOverlayClick(e) {
  if (e.target === e.currentTarget) {
    close();
  }
}

// Закрытие по Escape
function handleKeydown(e) {
  if (e.key === 'Escape' && isOpen.value) {
    close();
  }
}

function getShareUrl() {
  if (props.snippetUrl) {
    return props.snippetUrl;
  }
  // Используем текущий URL с параметром snippetId
  const url = new URL(window.location.href);
  url.searchParams.set('snippetId', props.snippetId);
  return url.toString();
}

async function handleShare(action) {
  const url = getShareUrl();
  const text = 'Посмотри этот трек!';

  switch (action) {
    case 'copy':
      try {
        await navigator.clipboard.writeText(url);
        // Можно добавить уведомление об успешном копировании
        alert('Ссылка скопирована!');
      } catch (err) {
        console.error('Ошибка копирования', err);
      }
      break;
    
    case 'telegram':
      window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`, '_blank');
      break;
    
    case 'whatsapp':
      window.open(`https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`, '_blank');
      break;
    
    case 'vk':
      window.open(`https://vk.com/share.php?url=${encodeURIComponent(url)}`, '_blank');
      break;
  }
  
  close();
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div>
    <!-- Кнопка поделиться -->
    <button
      class="flex flex-col items-center gap-1 text-white"
      @click.stop="open"
    >
      <i class="fa-solid fa-reply text-2xl"></i>
      <span class="text-xs">577</span>
    </button>

    <!-- Выплывающий блок поделиться (TikTok стиль) -->
    <Transition name="slide-up">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] flex flex-col"
        @click="handleOverlayClick"
      >
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        <!-- Панель поделиться -->
        <div
          class="relative comments-panel"
          @click.stop
        >
          <!-- Заголовок -->
          <div class="comments-panel-header">
            <h2 class="comments-panel-title">Поделиться</h2>
            <button
              @click="close"
              class="comments-panel-close-btn"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Список опций -->
          <div class="comments-panel-body overflow-y-auto max-h-[50vh]">
            <p class="comments-panel-count text-xs mb-2">В чат с друзьями</p>
            <div v-if="chatsLoading" class="flex justify-center py-4">
              <i class="fa-solid fa-spinner fa-spin text-black/50"></i>
            </div>
            <div v-else-if="chats.length === 0" class="text-black/50 text-sm py-2">
              Нет чатов. Напишите другу — чат появится здесь.
            </div>
            <ul v-else class="space-y-1 mb-4">
              <li
                v-for="chat in chats"
                :key="chat.id"
                class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                @click="(e) => { if (!e.target.closest('input')) toggleChat(chat.id); }"
              >
                <input
                  type="checkbox"
                  :checked="isChatSelected(chat.id)"
                  class="rounded border-gray-300"
                  @click.stop
                  @change="toggleChat(chat.id)"
                />
                <Avatar
                  :name="chat.other_user?.name"
                  :src="chat.other_user?.avatar_url"
                  :userId="chat.other_user?.id"
                  no-link
                />
                <span class="text-sm font-semibold text-black truncate">{{ chat.other_user?.name }}</span>
              </li>
            </ul>
            <button
              v-if="chats.length > 0 && selectedChatIds.length > 0"
              type="button"
              class="w-full py-2 rounded-full bg-black text-white text-sm font-semibold disabled:opacity-50"
              :disabled="shareToChatSubmitting"
              @click="shareToChats"
            >
              {{ shareToChatSubmitting ? 'Отправка...' : `Отправить в ${selectedChatIds.length}` }}
            </button>

            <p class="comments-panel-count text-xs mt-4 mb-2">Соцсети и ссылка</p>
            <div class="grid grid-cols-2 gap-4">
              <button
                v-for="option in shareOptions"
                :key="option.id"
                type="button"
                @click="handleShare(option.action)"
                class="flex flex-col items-center justify-center gap-3 p-6 rounded-xl bg-gray-100 hover:bg-gray-200 transition-colors"
              >
                <i :class="`${option.icon} text-3xl text-black`"></i>
                <span class="text-sm font-semibold text-black">{{ option.label }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: opacity 0.3s ease;
}

.slide-up-enter-active > div:last-child,
.slide-up-leave-active > div:last-child {
  transition: transform 0.3s ease;
}

.slide-up-enter-from {
  opacity: 0;
}

.slide-up-enter-from > div:last-child {
  transform: translateY(100%);
}

.slide-up-enter-to {
  opacity: 1;
}

.slide-up-enter-to > div:last-child {
  transform: translateY(0);
}

.slide-up-leave-from {
  opacity: 1;
}

.slide-up-leave-from > div:last-child {
  transform: translateY(0);
}

.slide-up-leave-to {
  opacity: 0;
}

.slide-up-leave-to > div:last-child {
  transform: translateY(100%);
}
</style>
