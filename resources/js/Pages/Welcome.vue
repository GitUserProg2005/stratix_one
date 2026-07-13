<script setup>
import LendingLayout from '@/Layouts/LendingLayout.vue';
import LendingHeroSection from '@/Components/Lending/LendingHeroSection.vue';
import LendingComponentsIntroSection from '@/Components/Lending/LendingComponentsIntroSection.vue';
import LendingClientsSection from '@/Components/Lending/LendingClientsSection.vue';
import LendingQuickStartSection from '@/Components/Lending/LendingQuickStartSection.vue';
import LendingOutlineSwiperSection from '@/Components/Lending/LendingOutlineSwiperSection.vue';
import LendingCtaBannerSection from '@/Components/Lending/LendingCtaBannerSection.vue';
import LendingServicesSection from '@/Components/Lending/LendingServicesSection.vue';
import About from '@/Components/Lending/About.vue';
import Integration from '@/Components/Lending/Integration.vue';
import LendingRatesSection from '@/Components/Lending/LendingRatesSection.vue';
import GeoFeatures from '@/Components/Lending/GeoFeatures.vue';
import LendingTestimonialsSection from '@/Components/Lending/LendingTestimonialsSection.vue';
</script>

<template>
    <div class="relative bg-content-glass">
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
    </div>
</template>
