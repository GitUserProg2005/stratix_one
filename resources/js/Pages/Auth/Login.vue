<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Вход" />

        <h1 class="title text-white mb-6">Вход</h1>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="email" class="block text-sm text-gray-400 mb-1">Email</label>
                <input
                    id="email"
                    type="email"
                    class="search-input block w-full text-white"
                    v-model="form.email"
                    required
                    autofocus
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
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-gray-500 text-[#7111EE] focus:ring-[#7111EE]" />
                <span class="ms-2 text-sm text-gray-400">Запомнить меня</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-gray-400 hover:text-white transition order-2 sm:order-1"
                >
                    Забыли пароль?
                </Link>
                <button
                    type="submit"
                    class="primary-btn w-full sm:w-auto order-1 sm:order-2 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Войти
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
