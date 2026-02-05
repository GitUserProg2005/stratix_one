<script setup>
import '../../css/custom.css';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-body flex flex-col items-center justify-center px-4">
        <Link href="/" class="mb-8 focus:outline-none focus:ring-0">
            <img src="/img/wix_logo3.png" alt="Logo" class="h-12 w-auto object-contain" />
        </Link>

        <div class="w-full max-w-md bg-content rounded-2xl p-6 sm:p-8 shadow-xl">
            <slot />
        </div>
    </div>
</template>
