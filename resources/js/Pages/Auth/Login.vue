<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
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

        <h1 class="title mb-2 t-color-content">Вход</h1>
        <p class="context mb-6">
            Войдите в аккаунт, чтобы продолжить работу.
        </p>

        <div v-if="status" class="mb-4 t-small t-color-primary">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="input mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Пароль" />
                <TextInput
                    id="password"
                    type="password"
                    class="input mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <Checkbox
                    name="remember"
                    v-model:checked="form.remember"
                    class="rounded border-gray-300 text-[var(--accent)] focus:ring-[var(--accent)]"
                />
                <span class="ms-2 t-small t-color-secondary">Запомнить меня</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="t-small t-color-secondary transition order-2 sm:order-1 inline-flex items-center gap-2"
                >
                    <i class="fa-solid fa-key text-xs" />
                    Забыли пароль?
                </Link>
                <button
                    type="submit"
                    class="primary-btn w-full sm:w-auto order-1 sm:order-2 inline-flex items-center justify-center gap-2"
                    :disabled="form.processing"
                >
                    Войти
                    <i class="fa-solid fa-right-to-bracket text-xs" />
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
