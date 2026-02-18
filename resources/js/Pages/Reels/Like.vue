<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
  snippetId: { type: Number, required: true },
  initialLiked: { type: Boolean, default: false },
  initialLikesCount: { type: Number, default: 0 },
})

const liked = ref(props.initialLiked)
const likesCount = ref(props.initialLikesCount)
const loading = ref(false)

function toggleLike() {
  if (loading.value) return

  loading.value = true

  axios.post(route('snippets.like', props.snippetId))
    .then(res => {
      liked.value = res.data.liked
      likesCount.value = res.data.likes_count
    })
    .catch(err => {
      console.error(err)
    })
    .finally(() => {
      loading.value = false
    })
}
</script>

<template>
  <button
    class="flex flex-col items-center gap-1 text-white"
    @click.stop="toggleLike"
  >
    <i
      :class="liked
        ? 'fa-solid fa-heart text-red-500'
        : 'fa-regular fa-heart text-white/90'"
      class="text-2xl"
    />
    <span class="text-xs">{{ likesCount }}</span>
  </button>
</template>
