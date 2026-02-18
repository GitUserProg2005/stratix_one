<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    avatar: null,
});

const avatarPreview = ref(null);
const isDragging = ref(false);

function handleFileSelect(event) {
    const file = event.target.files?.[0];
    if (file) {
        processFile(file);
    }
}

function handleDrop(event) {
    event.preventDefault();
    isDragging.value = false;
    
    const file = event.dataTransfer?.files?.[0];
    if (file) {
        processFile(file);
    }
}

function handleDragOver(event) {
    event.preventDefault();
    isDragging.value = true;
}

function handleDragLeave() {
    isDragging.value = false;
}

function processFile(file) {
    // Проверяем тип файла
    if (!file.type.startsWith('image/')) {
        alert('Пожалуйста, выберите изображение');
        return;
    }
    
    // Проверяем размер (макс 5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('Размер файла не должен превышать 5MB');
        return;
    }
    
    form.avatar = file;
    
    // Создаем превью
    const reader = new FileReader();
    reader.onload = (e) => {
        avatarPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
}

function removeAvatar() {
    form.avatar = null;
    avatarPreview.value = null;
}

const submit = () => {
    form.post(route('register'), {
        forceFormData: true,
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            avatarPreview.value = null;
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Регистрация" />

        <h1 class="title text-white mb-6">Регистрация</h1>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Поле аватара -->
            <div>
                <label class="block text-sm text-gray-400 mb-1">Аватар (необязательно)</label>
                <div
                    @drop="handleDrop"
                    @dragover="handleDragOver"
                    @dragleave="handleDragLeave"
                    :class="[
                        'border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer',
                        isDragging ? 'border-white bg-white/10' : 'border-gray-500 hover:border-gray-400'
                    ]"
                    @click="$refs.avatarInput.click()"
                >
                    <input
                        ref="avatarInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="handleFileSelect"
                    />
                    
                    <div v-if="!avatarPreview" class="space-y-2">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400"></i>
                        <p class="text-sm text-gray-400">
                            Перетащите изображение сюда или нажмите для выбора
                        </p>
                        <p class="text-xs text-gray-500">PNG, JPG до 5MB</p>
                    </div>
                    
                    <div v-else class="relative inline-block">
                        <img
                            :src="avatarPreview"
                            alt="Preview"
                            class="w-24 h-24 rounded-full object-cover mx-auto"
                        />
                        <button
                            type="button"
                            @click.stop="removeAvatar"
                            class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors"
                        >
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                </div>
                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <div>
                <label for="name" class="block text-sm text-gray-400 mb-1">Имя</label>
                <input
                    id="name"
                    type="text"
                    class="search-input block w-full text-white"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <label for="email" class="block text-sm text-gray-400 mb-1">Email</label>
                <input
                    id="email"
                    type="email"
                    class="search-input block w-full text-white"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="block text-sm text-gray-400 mb-1">Пароль</label>
                <input
                    id="password"
                    type="password"
                    class="search-input block w-full text-white"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm text-gray-400 mb-1">Подтверждение пароля</label>
                <input
                    id="password_confirmation"
                    type="password"
                    class="search-input block w-full text-white"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                <Link
                    :href="route('login')"
                    class="text-sm text-gray-400 hover:text-white transition order-2 sm:order-1"
                >
                    Уже есть аккаунт?
                </Link>
                <button
                    type="submit"
                    class="primary-btn w-full sm:w-auto order-1 sm:order-2 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Зарегистрироваться
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
