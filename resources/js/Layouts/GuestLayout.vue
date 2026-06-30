<script setup>
import '../../css/custom.css';
import AuthBrand from '@/Components/Auth/AuthBrand.vue';
</script>

<template>
    <div class="min-h-screen auth-grid-bg flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md content rounded-3xl p-6 sm:p-8">
            <div class="mb-8">
                <AuthBrand />
            </div>
            <slot />
        </div>
    </div>
</template>
