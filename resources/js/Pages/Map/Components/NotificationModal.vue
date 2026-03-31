<script setup>
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ message: { type: String, default: '' } });
const isOpen = ref(false);
const internalMessage = ref('');

function open(message) {
  internalMessage.value = message ?? props.message;
  isOpen.value = true;
}
function close() {
  isOpen.value = false;
}
defineExpose({ open, close });
</script>

<template>
  <Modal :show="isOpen" max-width="sm" @close="close">
    <div class="p-4 sm:p-5">
      <p class="t-body">{{ internalMessage || 'Уведомление' }}</p>
      <div class="mt-4">
        <button type="button" class="w-full inline-flex items-center justify-center primary-btn" @click="close">Понятно</button>
      </div>
    </div>
  </Modal>
</template>
