<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useMap } from '@/composables/useMap';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);

const containerRef = ref(null);
const { map, initMap } = useMap(containerRef);
const points = ref([...props.modelValue]);

function updatePolygon() {
    if (!map.value) return;

    const coordinates = [...points.value];

    if (coordinates.length > 2) {
        coordinates.push(coordinates[0]);
    }

    const geojsonData = {
        type: 'Feature',
        geometry: {
            type: 'Polygon',
            coordinates: [coordinates]
        }
    };

    if (map.value.getSource('polygon-source')) {
        map.value.getSource('polygon-source').setData(geojsonData);
    } else {
        map.value.addSource('polygon-source', {
            type: 'geojson',
            data: geojsonData
        });

        // Заливка полигона (полупрозрачный изумрудный неон)
        map.value.addLayer({
            id: 'polygon-fill',
            type: 'fill',
            source: 'polygon-source',
            paint: {
                'fill-color': '#10b981',
                'fill-opacity': 0.15
            }
        });

        // Контур полигона (яркая неоновая линия)
        map.value.addLayer({
            id: 'polygon-outline',
            type: 'line',
            source: 'polygon-source',
            paint: {
                'line-color': '#10b981',
                'line-width': 2
            }
        });
    }
}

// Сбросить полигон, если нужно
const clearPolygon = () => {
    points.value = [];
    if (map.value && map.value.getSource('polygon-source')) {
        map.value.getSource('polygon-source').setData({
            type: 'Feature',
            geometry: { type: 'Polygon', coordinates: [[]] }
        });
    }
    emit('update:modelValue', []);
};

let resizeObserver = null;

onMounted(async () => {
    await nextTick();
    await initMap();

    if (!map.value || !containerRef.value) {
        return;
    }

    const resize = () => map.value?.resize();

    resize();
    requestAnimationFrame(resize);
    setTimeout(resize, 350);

    resizeObserver = new ResizeObserver(resize);
    resizeObserver.observe(containerRef.value);

    map.value.on('style.load', () => {
        if (points.value.length > 0) updatePolygon();
    });

    map.value.on('click', (e) => {
        points.value.push([e.lngLat.lng, e.lngLat.lat]);
        
        updatePolygon();
        emit('update:modelValue', points.value);
    });
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
});

watch(() => props.modelValue, (newVal) => {
    if (JSON.stringify(newVal) !== JSON.stringify(points.value)) {
        points.value = [...newVal];
        updatePolygon();
    }
}, { deep: true });
</script>

<template>
    <div class="relative w-full">
        <div
            ref="containerRef"
            class="h-72 min-h-[280px] w-full overflow-hidden rounded-xl border border-black/15 dark:border-white/15"
        />

        <button
            type="button"
            class="absolute right-2 top-2 primary-btn"
            @click="clearPolygon"
        >
            <i class="fa-solid fa-eraser"></i> 
            Очистить охват
        </button>
    </div>
</template>
