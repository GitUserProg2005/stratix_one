<script setup>
import { ref, watch, computed, nextTick } from 'vue'
import Modal from './Modal.vue'

const props = defineProps({
  currentTime: {
    type: Number,
    default: 0
  },
  lyrics: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['seek'])

const showModal = ref(false)

// refs для строк текста
const lineRefs = ref([])

function toggleModal() {
  showModal.value = !showModal.value
}

// вычисляем текущую строку
const currentLineIndex = computed(() => {
  if (!props.lyrics.length) return -1

  const time = Number(props.currentTime)

  // ищем последнюю строку, которая уже началась
  for (let i = props.lyrics.length - 1; i >= 0; i--) {
    if (time >= Number(props.lyrics[i].start_time)) {
      return i
    }
  }

  return -1
})

function scrollToLine(index) {
  if (index < 0) return
  const el = lineRefs.value[index]
  if (!el) return
  el.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

// авто-скролл к текущей строке (только когда модалка открыта)
watch(currentLineIndex, (index) => {
  if (index < 0 || !showModal.value) return
  scrollToLine(index)
})

// при открытии модалки — сразу к текущей строке
watch(showModal, (open) => {
  if (open) nextTick(() => scrollToLine(currentLineIndex.value))
})

function lineClass(index) {
  if (index === currentLineIndex.value) {
    return 'text-green-400 text-xl font-semibold scale-105'
  }

  if (Math.abs(index - currentLineIndex.value) === 1) {
    return 'text-gray-300 opacity-70'
  }

  return 'text-gray-500 opacity-40'
}

function selectLine(index) {
  const line = props.lyrics[index]
  if (!line || line.start_time == null) return
  const time = Number(line.start_time)
  if (!Number.isFinite(time) || time < 0) return
  emit('seek', time)
}
</script>

<template>
  <button @click="toggleModal">
    <i class="fa-solid fa-music"></i>
  </button>

  <Modal v-model:show="showModal">
    <div class="bg-body h-80 px-6 py-4 flex flex-col">

      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-white font-semibold"><i class="fa-solid fa-quote-right mr-2"></i> Караоке</h3>
        <button
          @click="toggleModal"
          class="text-gray-400 hover:text-white transition"
        >
          <i class="fa-solid fa-xmark text-xl"></i>
        </button>
      </div>

      <!-- Karaoke viewport -->
      <div class="flex-1 overflow-y-auto overflow-x-hidden scroll-smooth">

        <div
          v-for="(line, index) in lyrics"
          :key="index"
          :ref="el => lineRefs[index] = el"
          class="min-h-8 py-2 px-3 max-w-full flex items-center justify-center text-center
          transition-all duration-300 cursor-pointer
          break-words overflow-wrap-anywhere leading-relaxed"
          :class="lineClass(index)"
          @click.stop="selectLine(index)"
        >
          {{ line.line || line.text }}
        </div>

      </div>
    </div>
  </Modal>
</template>
