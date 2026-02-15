<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  snippetId: { type: Number, required: true },
  initialReposted: { type: Boolean, default: false },
  initialRepostedByFriends: { type: Array, default: () => [] },
});

const emit = defineEmits(['update']);

const isReposted = ref(props.initialReposted);
const repostedByFriends = ref([...props.initialRepostedByFriends]);
const loading = ref(false);

function toggleRepost() {
  if (loading.value) return;
  loading.value = true;
  axios
    .post(route('snippets.repost', props.snippetId))
    .then((res) => {
      isReposted.value = res.data.is_reposted;
      repostedByFriends.value = res.data.reposted_by_friends ?? [];
      emit('update', { is_reposted: res.data.is_reposted, reposted_by_friends: repostedByFriends.value });
    })
    .catch((err) => console.error(err))
    .finally(() => {
      loading.value = false;
    });
}
</script>

<template>
  <button
    type="button"
    class="flex flex-col items-center gap-1 text-white"
    @click.stop="toggleRepost"
  >
    <i
      :class="[
        'text-2xl',
        isReposted ? 'fa-solid fa-repeat text-green-500' : 'fa-solid fa-repeat text-white/90'
      ]"
    />
    <span class="text-xs">Репост</span>
  </button>
</template>
