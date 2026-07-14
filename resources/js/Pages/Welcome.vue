<script setup>
import LendingLayout from '@/Layouts/LendingLayout.vue';
import LendingHeroSection from '@/Components/Lending/LendingHeroSection.vue';
import About from '@/Components/Lending/About.vue';
import Integration from '@/Components/Lending/Integration.vue';
import LendingRatesSection from '@/Components/Lending/LendingRatesSection.vue';
import GeoFeatures from '@/Components/Lending/GeoFeatures.vue';
import LendingTestimonialsSection from '@/Components/Lending/LendingTestimonialsSection.vue';
</script>

<template>
    <LendingLayout>
            <template #hero>
                <LendingHeroSection />
                <div class="w-full laser-line"></div>
            </template>
            <About />
            <Integration />
            <LendingRatesSection />
            <GeoFeatures />
            <LendingTestimonialsSection />
        </LendingLayout>
</template>
