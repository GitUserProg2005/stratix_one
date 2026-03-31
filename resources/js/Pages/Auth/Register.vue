<script setup>
import { computed, ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  role: 'passenger',
  avatar: null,
  vehicle_picture: null,
  vehicle_brand: '',
  vehicle_model: '',
  vehicle_color: '',
  vehicle_license_plate: '',
});

const avatarPreview = ref(null);
const vehiclePreview = ref(null);
const isDragging = ref(false);
const isDraggingVehicle = ref(false);
const isDriver = computed(() => form.role === 'driver');

function processFile(file, onOk) {
  if (!file.type.startsWith('image/')) return;
  if (file.size > 5 * 1024 * 1024) return;
  const reader = new FileReader();
  reader.onload = (e) => onOk(file, e.target.result);
  reader.readAsDataURL(file);
}

function handleFileSelect(event) {
  const file = event.target.files?.[0];
  if (file) processFile(file, (f, dataUrl) => { form.avatar = f; avatarPreview.value = dataUrl; });
}
function handleVehicleFileSelect(event) {
  const file = event.target.files?.[0];
  if (file) processFile(file, (f, dataUrl) => { form.vehicle_picture = f; vehiclePreview.value = dataUrl; });
}
function handleDrop(event) {
  event.preventDefault();
  isDragging.value = false;
  const file = event.dataTransfer?.files?.[0];
  if (file) processFile(file, (f, dataUrl) => { form.avatar = f; avatarPreview.value = dataUrl; });
}
function handleVehicleDrop(event) {
  event.preventDefault();
  isDraggingVehicle.value = false;
  const file = event.dataTransfer?.files?.[0];
  if (file) processFile(file, (f, dataUrl) => { form.vehicle_picture = f; vehiclePreview.value = dataUrl; });
}

const submit = () => {
  form.post(route('register'), {
    forceFormData: true,
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Регистрация" />

    <h1 class="title mb-2">Регистрация</h1>
    <p class="context mb-6">Создайте аккаунт для работы в сервисе.</p>

    <form @submit.prevent="submit" class="space-y-5">
      <div>
        <label class="t-mini text-gray-600 mb-1 block">Кто вы?</label>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.role" type="radio" name="role" value="passenger" class="rounded border-gray-400 text-black focus:ring-black" />
            <span class="text-gray-900">Пассажир</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.role" type="radio" name="role" value="driver" class="rounded border-gray-400 text-black focus:ring-black" />
            <span class="text-gray-900">Водитель</span>
          </label>
        </div>
        <InputError class="mt-2" :message="form.errors.role" />
      </div>

      <div
        @drop="handleDrop"
        @dragover.prevent="isDragging = true"
        @dragleave="isDragging = false"
        :class="['border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer bg-gray-50', isDragging ? 'border-gray-800 bg-gray-100' : 'border-gray-300 hover:border-gray-400']"
        @click="$refs.avatarInput.click()"
      >
        <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="handleFileSelect" />
        <div v-if="!avatarPreview" class="space-y-2">
          <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400"></i>
          <p class="t-small text-gray-600">Аватар (необязательно)</p>
        </div>
        <img v-else :src="avatarPreview" alt="Preview" class="w-24 h-24 rounded-full object-cover mx-auto" />
      </div>
      <InputError class="mt-2" :message="form.errors.avatar" />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="t-mini text-gray-600 mb-1 block">Имя</label>
          <input v-model="form.name" type="text" class="input block w-full" required autocomplete="name" />
          <InputError class="mt-2" :message="form.errors.name" />
        </div>
        <div>
          <label class="t-mini text-gray-600 mb-1 block">Email</label>
          <input v-model="form.email" type="email" class="input block w-full" required autocomplete="username" />
          <InputError class="mt-2" :message="form.errors.email" />
        </div>
      </div>

      <div>
        <label class="t-mini text-gray-600 mb-1 block">Телефон</label>
        <input v-model="form.phone" type="tel" class="input block w-full" placeholder="79001234567" />
        <InputError class="mt-2" :message="form.errors.phone" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="t-mini text-gray-600 mb-1 block">Пароль</label>
          <input v-model="form.password" type="password" class="input block w-full" required autocomplete="new-password" />
          <InputError class="mt-2" :message="form.errors.password" />
        </div>
        <div>
          <label class="t-mini text-gray-600 mb-1 block">Подтверждение пароля</label>
          <input v-model="form.password_confirmation" type="password" class="input block w-full" required autocomplete="new-password" />
          <InputError class="mt-2" :message="form.errors.password_confirmation" />
        </div>
      </div>

      <template v-if="isDriver">
        <div
          @drop="handleVehicleDrop"
          @dragover.prevent="isDraggingVehicle = true"
          @dragleave="isDraggingVehicle = false"
          :class="['border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer bg-gray-50', isDraggingVehicle ? 'border-gray-800 bg-gray-100' : 'border-gray-300 hover:border-gray-400']"
          @click="$refs.vehicleInput.click()"
        >
          <input ref="vehicleInput" type="file" accept="image/*" class="hidden" @change="handleVehicleFileSelect" />
          <p v-if="!vehiclePreview" class="t-small text-gray-600">Фото машины</p>
          <img v-else :src="vehiclePreview" alt="Vehicle" class="max-h-32 rounded-lg object-cover mx-auto" />
        </div>
        <InputError class="mt-2" :message="form.errors.vehicle_picture" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <input v-model="form.vehicle_license_plate" type="text" class="input block w-full" placeholder="Гос. номер" />
          <input v-model="form.vehicle_brand" type="text" class="input block w-full" placeholder="Марка" />
          <input v-model="form.vehicle_model" type="text" class="input block w-full" placeholder="Модель" />
          <input v-model="form.vehicle_color" type="text" class="input block w-full" placeholder="Цвет" />
        </div>
      </template>

      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
        <Link :href="route('login')" class="t-small text-gray-600 hover:text-gray-900 transition order-2 sm:order-1">Уже есть аккаунт?</Link>
        <button type="submit" class="primary-btn w-full sm:w-auto order-1 sm:order-2" :disabled="form.processing">Зарегистрироваться</button>
      </div>
    </form>
  </GuestLayout>
</template>
