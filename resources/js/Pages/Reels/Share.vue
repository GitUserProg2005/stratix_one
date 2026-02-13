<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  snippetId: { type: Number, required: true },
  snippetUrl: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const isOpen = ref(false);

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

function open() {
  isOpen.value = true;
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
          <div class="comments-panel-body">
            <div class="grid grid-cols-2 gap-4">
              <button
                v-for="option in shareOptions"
                :key="option.id"
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
