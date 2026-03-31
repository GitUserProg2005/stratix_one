<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  modelValue: { type: Object, default: null },
  placeholder: { type: String, default: 'Введите адрес...' },
});
const emit = defineEmits(['update:modelValue', 'results']);
const query = ref(props.modelValue?.display_name ?? '');
const results = ref([]);
const loading = ref(false);
const open = ref(false);
let searchTimeout = null;

watch(() => props.modelValue, (v) => { query.value = v?.display_name ?? ''; }, { immediate: true });

async function search() {
  const q = query.value.trim();
  if (!q) {
    results.value = [];
    emit('results', []);
    return;
  }
  loading.value = true;
  try {
    const { data } = await axios.get(route('map.search'), { params: { q, limit: 8 } });
    results.value = Array.isArray(data) ? data : [];
    emit('results', results.value);
    open.value = true;
  } catch {
    results.value = [];
    emit('results', []);
  } finally {
    loading.value = false;
  }
}

watch(query, (q) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  if (!q.trim()) {
    results.value = [];
    open.value = false;
    emit('results', []);
    return;
  }
  searchTimeout = setTimeout(search, 320);
});

function select(place) {
  open.value = false;
  query.value = place.display_name;
  emit('update:modelValue', place);
  emit('results', []);
}

function clear() {
  query.value = '';
  results.value = [];
  open.value = false;
  emit('update:modelValue', null);
  emit('results', []);
}
</script>

<template>
  <div class="relative w-full bg-content-outline rounded-lg border border-black/10 dark:border-white/10">
    <div class="relative flex items-center">
      <input v-model="query" type="text" :placeholder="placeholder" class="w-full pl-3 pr-12 py-3 bg-transparent border-0 focus:ring-0 focus:outline-none t-body" autocomplete="off" />
      <button v-if="query" type="button" class="absolute right-2 p-1.5 rounded-lg context hover:opacity-80 hover:bg-content" aria-label="Очистить" @click="clear">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    </div>
    <div v-if="loading" class="px-4 py-2 t-small border-t border-black/10 dark:border-white/10">Поиск...</div>
    <ul v-else-if="open && results.length" class="max-h-48 overflow-y-auto border-t border-black/10 dark:border-white/10 divide-y divide-black/10 dark:divide-white/10">
      <li v-for="place in results" :key="place.place_id" class="px-4 py-3 cursor-pointer text-left transition-colors hover:bg-content" @click="select(place)">
        <p class="t-small font-medium truncate text-accent">{{ place.display_name }}</p>
      </li>
    </ul>
    <div v-else-if="open && query && !loading" class="px-4 py-3 t-small border-t border-black/10 dark:border-white/10">Ничего не найдено</div>
  </div>
</template>
