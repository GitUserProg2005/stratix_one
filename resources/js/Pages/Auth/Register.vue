<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Регистрация" />

        <h1 class="title text-white mb-6">Регистрация</h1>

        <form @submit.prevent="submit" class="space-y-5">
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
