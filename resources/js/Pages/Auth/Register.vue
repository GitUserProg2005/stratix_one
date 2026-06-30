<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
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

        <h1 class="title-2 mb-2 t-color-content">Регистрация</h1>
        <p class="context mb-6">Создайте аккаунт для работы в сервисе.</p>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="name" value="Имя" />
                <TextInput id="name" type="text" class="input mt-1 block w-full" v-model="form.name" required autofocus autocomplete="name" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />
                <TextInput id="email" type="email" class="input mt-1 block w-full" v-model="form.email" required autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Пароль" />
                <TextInput id="password" type="password" class="input mt-1 block w-full" v-model="form.password" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Подтверждение пароля" />
                <TextInput id="password_confirmation" type="password" class="input mt-1 block w-full" v-model="form.password_confirmation" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                <Link :href="route('login')" class="t-small t-color-secondary transition order-2 sm:order-1 inline-flex items-center gap-2">
                    <i class="fa-solid fa-right-to-bracket text-xs" />
                    Уже есть аккаунт?
                </Link>
                <button type="submit" class="primary-btn w-full sm:w-auto order-1 sm:order-2 inline-flex items-center justify-center gap-2" :disabled="form.processing">
                    Создать аккаунт
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
