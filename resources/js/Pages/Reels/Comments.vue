<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Avatar from '@/Components/Avatar.vue';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);

const props = defineProps({
  snippetId: { type: Number, required: true },
  initialCommentsCount: { type: Number, default: 0 },
});

const emit = defineEmits(['close']);

const isOpen = ref(false);
const comments = ref([]);
const isLoading = ref(false);
const newComment = ref('');
const isSubmitting = ref(false);
const commentsCount = ref(props.initialCommentsCount);

const formattedCount = computed(() => {
  if (commentsCount.value >= 1000) {
    return `${(commentsCount.value / 1000).toFixed(1)} тыс.`;
  }
  return commentsCount.value.toString();
});

async function loadComments() {
  if (isLoading.value) return;
  
  isLoading.value = true;
  try {
    const { data } = await axios.get(route('snippets.comments', props.snippetId));
    comments.value = data.comments;
    commentsCount.value = data.comments_count;
  } catch (error) {
    console.error('Ошибка загрузки комментариев', error);
  } finally {
    isLoading.value = false;
  }
}

async function submitComment() {
  if (!newComment.value.trim() || isSubmitting.value) return;

  isSubmitting.value = true;
  try {
    const { data } = await axios.post(route('snippets.comments.create', props.snippetId), {
      content: newComment.value.trim(),
    });
    
    comments.value.unshift(data.comment);
    commentsCount.value = data.comments_count;
    newComment.value = '';
  } catch (error) {
    console.error('Ошибка создания комментария', error);
  } finally {
    isSubmitting.value = false;
  }
}

function open() {
  isOpen.value = true;
  loadComments();
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

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown);
});

watch(() => props.snippetId, () => {
  if (isOpen.value) {
    loadComments();
  }
});
</script>

<template>
  <div>
    <!-- Кнопка комментариев -->
    <button
      class="flex flex-col items-center gap-1 text-white"
      @click.stop="open"
    >
      <i class="fa-solid fa-comment text-2xl"></i>
      <span class="text-xs">{{ formattedCount }}</span>
    </button>

    <!-- Выплывающий блок комментариев (TikTok стиль) -->
    <Transition name="slide-up">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] flex flex-col"
        @click="handleOverlayClick"
      >
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        <!-- Панель комментариев -->
        <div
          class="relative comments-panel"
          @click.stop
        >
          <!-- Заголовок -->
          <div class="comments-panel-header">
            <div class="flex items-center gap-3">
                <span class="comments-panel-count text-xs">{{ formattedCount }}</span>
                <h2 class="comments-panel-title">Комментарии</h2>
            </div>
            <button
              @click="close"
              class="comments-panel-close-btn"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Список комментариев -->
          <div class="comments-panel-body">
            <div v-if="isLoading" class="comments-panel-loading">
              <i class="fa-solid fa-spinner fa-spin comments-panel-loading-icon"></i>
            </div>

            <div v-else-if="comments.length === 0" class="comments-panel-empty">
              <i class="fa-solid fa-comment-slash text-4xl mb-4"></i>
              <p>Пока нет комментариев</p>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="comment in comments"
                :key="comment.id"
                class="comments-panel-comment-item"
              >
                <!-- Аватар -->
                <div class="flex-shrink-0">
                  <Avatar :name="comment.user.name" :src="comment.user.avatar_url" />
                </div>

                <!-- Контент комментария -->
                <div class="comments-panel-comment-content">
                  <div class="comments-panel-comment-header">
                    <span class="comments-panel-comment-name">{{ comment.user.name }}</span>
                    <span class="comments-panel-comment-time">{{ comment.created_at }}</span>
                  </div>
                  <p class="comments-panel-comment-text">{{ comment.content }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Форма добавления комментария -->
          <div class="comments-panel-form">
            <form @submit.prevent="submitComment" class="flex items-center gap-2">
              <Avatar v-if="currentUser" :name="currentUser.name" :src="currentUser.avatar_url" />
              <input
                v-model="newComment"
                type="text"
                placeholder="Добавить комментарий..."
                class="comments-panel-input"
                :disabled="isSubmitting"
              />
              <button
                type="submit"
                :disabled="!newComment.trim() || isSubmitting"
                class="comments-panel-submit-btn"
              >
                <i v-if="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                <span v-else><i class="fa-solid fa-arrow-up"></i></span>
              </button>
            </form>
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
