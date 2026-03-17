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

        <h1 class="title mb-2">Регистрация</h1>
        <p class="context mb-6">
            Создайте аккаунт, чтобы использовать все возможности сервиса.
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Поле аватара -->
            <div>
                <label class="t-mini text-gray-600 mb-1 block">Аватар (необязательно)</label>
                <div
                    @drop="handleDrop"
                    @dragover="handleDragOver"
                    @dragleave="handleDragLeave"
                    :class="[
                        'border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer bg-gray-50',
                        isDragging ? 'border-gray-800 bg-gray-100' : 'border-gray-300 hover:border-gray-400'
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
                        <p class="t-small text-gray-600">
                            Перетащите изображение сюда или нажмите для выбора
                        </p>
                        <p class="t-mini text-gray-500">PNG, JPG до 5MB</p>
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="t-mini text-gray-600 mb-1 block">Имя</label>
                    <input
                        id="name"
                        type="text"
                        class="input block w-full"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <label for="email" class="t-mini text-gray-600 mb-1 block">Email</label>
                    <input
                        id="email"
                        type="email"
                        class="input block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="t-mini text-gray-600 mb-1 block">Пароль</label>
                    <input
                        id="password"
                        type="password"
                        class="input block w-full"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <label for="password_confirmation" class="t-mini text-gray-600 mb-1 block">Подтверждение пароля</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        class="input block w-full"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                <Link
                    :href="route('login')"
                    class="t-small text-gray-600 hover:text-gray-900 transition order-2 sm:order-1"
                >
                    Уже есть аккаунт?
                </Link>
                <button
                    type="submit"
                    class="primary-btn w-full sm:w-auto order-1 sm:order-2"
                    :disabled="form.processing"
                >
                    Зарегистрироваться
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
