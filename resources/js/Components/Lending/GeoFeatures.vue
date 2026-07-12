<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import Globe from 'globe.gl';

const features = [
    {
        title: 'Точка в территории',
        text: 'Проверяйте, попадает ли точка в зону доставки или геофence — через PostGIS прямо в сценарии.',
    },
    {
        title: 'OSRM',
        text: 'Стройте маршруты из A в B, оптимизируйте цепочки и находите ближайшего исполнителя.',
    },
    {
        title: 'Карты и геоданные',
        text: 'Подключайте тайлы, полигоны и координаты — всё в одном графовом редакторе.',
    },
];

const markers = [
    
];

const globeContainer = ref(null);
let globeInstance = null;
let resizeObserver = null;

function syncGlobeSize() {
    if (!globeInstance || !globeContainer.value) {
        return;
    }

    const { clientWidth, clientHeight } = globeContainer.value;
    globeInstance.width(clientWidth).height(clientHeight);
}

function styleGraticules() {
    if (!globeInstance) {
        return;
    }

    globeInstance.scene().traverse((obj) => {
        if (obj.type !== 'LineSegments' || !obj.material?.color) {
            return;
        }

        obj.material.color.set('#e8dfd5');
        obj.material.opacity = 0.32;
        obj.material.transparent = true;
    });
}

function initGlobe() {
    if (!globeContainer.value || globeInstance) {
        return;
    }

    globeInstance = new Globe(globeContainer.value, {
        animateIn: false,
        waitForGlobeReady: false,
        rendererConfig: { alpha: true, antialias: true },
    })
        .backgroundColor('rgba(0,0,0,0)')
        .showGlobe(false)
        .showGraticules(true)
        .showAtmosphere(true)
        .atmosphereColor('#8ec5ff')
        .atmosphereAltitude(0.24)
        .htmlElementsData(markers)
        .htmlElement(() => {
            const el = document.createElement('div');
            el.className = 'geo-feature-marker';
            el.textContent = '*';
            return el;
        })
        .enablePointerInteraction(false)
        .pointOfView({ lat: 8, lng: -28, altitude: 2.15 }, 0);

    globeInstance.renderer().setClearColor(0x000000, 0);

    const controls = globeInstance.controls();
    controls.autoRotate = true;
    controls.autoRotateSpeed = 0.55;
    controls.enableZoom = false;
    controls.enablePan = false;
    controls.enableRotate = false;

    globeInstance.onGlobeReady(styleGraticules);
    syncGlobeSize();
}

onMounted(async () => {
    await nextTick();
    initGlobe();

    if (globeContainer.value) {
        resizeObserver = new ResizeObserver(syncGlobeSize);
        resizeObserver.observe(globeContainer.value);
    }
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    globeInstance?._destructor?.();
    globeInstance = null;
});
</script>

<template>
    <section class="mt-16">
        <div class="geo-features-stage">
            <div ref="globeContainer" class="geo-globe" aria-hidden="true" />

            <div class="geo-features-head">
                <h2 class="title">Гео-технологии в ваших сценариях</h2>
                <p class="context mt-4">
                    Маршруты, зоны и координаты — без отдельного GIS-сервиса. Всё работает внутри ваших workflow.
                </p>
            </div>

            <article
                v-for="(feature, index) in features"
                :key="feature.title"
                class="auth-grid-bg rounded-2xl !p-4 geo-feature"
                :class="`geo-feature--${index + 1}`"
            >
                <h3 class="title-font-3 text-base">{{ feature.title }}</h3>
                <p class="t-body text-sm mt-2 leading-relaxed">{{ feature.text }}</p>
            </article>
        </div>
    </section>
</template>

<style scoped>
.geo-features-stage {
    position: relative;
    min-height: 36rem;
}

.geo-features-head {
    position: absolute;
    top: 0;
    left: 50%;
    z-index: 2;
    width: min(100%, 36rem);
    transform: translateX(-50%);
    text-align: center;
}

.geo-globe {
    position: absolute;
    left: 50%;
    top: 50%;
    width: min(72vw, 26rem);
    height: min(72vw, 26rem);
    transform: translate(-50%, -50%);
    background: transparent;
    z-index: 1;
}

.geo-globe :deep(canvas) {
    background: transparent !important;
}

.geo-feature {
    position: absolute;
    z-index: 2;
    width: min(100%, 15rem);
}

.geo-feature--1 {
    top: 28%;
    left: 0;
}

.geo-feature--2 {
    top: 44%;
    right: 0;
}

.geo-feature--3 {
    bottom: 4%;
    left: 50%;
    transform: translateX(-50%);
}

@media (max-width: 767px) {
    .geo-features-stage {
        min-height: auto;
        padding-top: 7rem;
        padding-bottom: 1rem;
    }

    .geo-features-head {
        position: relative;
        top: auto;
        left: auto;
        transform: none;
        width: 100%;
    }

    .geo-globe {
        position: relative;
        left: auto;
        top: auto;
        transform: none;
        width: 100%;
        height: auto;
        aspect-ratio: 1;
        margin: 2rem auto 0;
    }

    .geo-feature {
        position: relative;
        top: auto;
        right: auto;
        bottom: auto;
        left: auto;
        transform: none;
        width: 100%;
        margin-top: 0.75rem;
    }
}
</style>

<style>
.geo-feature-marker {
    color: #f5a683;
    font-size: 2rem;
    line-height: 1;
    font-weight: 400;
    pointer-events: none;
    user-select: none;
}
</style>
