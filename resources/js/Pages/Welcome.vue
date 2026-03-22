<script setup>
import LendingLayout from '@/Layouts/LendingLayout.vue';
import LendingHeroSection from '@/Components/Lending/LendingHeroSection.vue';
import LendingComponentsIntroSection from '@/Components/Lending/LendingComponentsIntroSection.vue';
import LendingClientsSection from '@/Components/Lending/LendingClientsSection.vue';
import LendingQuickStartSection from '@/Components/Lending/LendingQuickStartSection.vue';
import LendingOutlineSwiperSection from '@/Components/Lending/LendingOutlineSwiperSection.vue';
import LendingCtaBannerSection from '@/Components/Lending/LendingCtaBannerSection.vue';
import LendingServicesSection from '@/Components/Lending/LendingServicesSection.vue';
</script>

<template>
    <div class="relative bg-body">
        <LendingLayout>
            <LendingHeroSection />
            <LendingComponentsIntroSection />
            <LendingClientsSection />
            <LendingQuickStartSection />
            <LendingOutlineSwiperSection />
            <LendingCtaBannerSection />
            <LendingServicesSection />
        </LendingLayout>
    </div>
</template>
