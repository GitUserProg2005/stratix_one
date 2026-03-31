import { ref, onUnmounted } from 'vue';
import maplibregl from 'maplibre-gl';
import { useMapServices } from '@/composables/useMapServices.js';

const DEFAULT_CENTER = [37.6173, 55.7558];
const DEFAULT_ZOOM = 10;

function escapeHtml(text) {
  if (text == null) return '';
  return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

export function useMap(containerRef) {
  const { tileserverStyleUrl } = useMapServices();
  const map = ref(null);
  const resultMarkers = ref([]);
  const markerA = ref(null);
  const markerB = ref(null);

  function createMarkerElement(className) {
    const el = document.createElement('div');
    el.className = className;
    const isResult = className === 'map-marker-result';
    el.style.width = isResult ? '12px' : '16px';
    el.style.height = isResult ? '12px' : '16px';
    el.style.borderRadius = '50%';
    el.style.backgroundColor = '#3b82f6';
    el.style.border = isResult ? '2px solid white' : '3px solid white';
    el.style.boxShadow = isResult ? '0 0 0 1px #3b82f6' : '0 0 0 2px #3b82f6';
    el.style.flexShrink = '0';
    return el;
  }

  function addResultMarkers(places, mapInstance) {
    const m = mapInstance || map.value;
    if (!m) return;
    resultMarkers.value.forEach((mark) => mark.remove());
    resultMarkers.value = [];
    const bounds = new maplibregl.LngLatBounds();

    places.forEach((place) => {
      const lon = parseFloat(place.lon);
      const lat = parseFloat(place.lat);
      if (Number.isNaN(lon) || Number.isNaN(lat)) return;
      const marker = new maplibregl.Marker({ element: createMarkerElement('map-marker-result') })
        .setLngLat([lon, lat])
        .setPopup(new maplibregl.Popup({ offset: 12, className: 'map-place-popup', closeButton: false, closeOnClick: false })
          .setHTML(`<div class="map-place-label">${escapeHtml(place.display_name)}</div>`))
        .addTo(m);
      resultMarkers.value.push(marker);
      bounds.extend([lon, lat]);
    });

    if (resultMarkers.value.length) {
      m.fitBounds(bounds, { padding: 80, maxZoom: 12, duration: 600 });
      resultMarkers.value[0].togglePopup();
    }
  }

  function clearResultMarkers() {
    resultMarkers.value.forEach((m) => m.remove());
    resultMarkers.value = [];
  }

  function setMarkerA(place, mapInstance) {
    const m = mapInstance || map.value;
    if (!m || !place) return;
    if (markerA.value) markerA.value.remove();
    const lon = parseFloat(place.lon);
    const lat = parseFloat(place.lat);
    if (Number.isNaN(lon) || Number.isNaN(lat)) return;
    const name = place.display_name != null ? place.display_name : '';
    markerA.value = new maplibregl.Marker({ element: createMarkerElement('map-marker') })
      .setLngLat([lon, lat])
      .setPopup(new maplibregl.Popup({ offset: 12, className: 'map-place-popup', closeButton: false, closeOnClick: false })
        .setHTML(`<div class="map-place-label-a truncate">${escapeHtml(name)}</div>`))
      .addTo(m);
    markerA.value.togglePopup();
  }

  function setMarkerB(place, mapInstance) {
    const m = mapInstance || map.value;
    if (!m || !place) return;
    if (markerB.value) markerB.value.remove();
    const lon = parseFloat(place.lon);
    const lat = parseFloat(place.lat);
    if (Number.isNaN(lon) || Number.isNaN(lat)) return;
    const name = place.display_name != null ? place.display_name : '';
    markerB.value = new maplibregl.Marker({ element: createMarkerElement('map-marker') })
      .setLngLat([lon, lat])
      .setPopup(new maplibregl.Popup({ offset: 12, className: 'map-place-popup', closeButton: false, closeOnClick: false })
        .setHTML(`<div class="map-place-label-b truncate">${escapeHtml(name)}</div>`))
      .addTo(m);
    markerB.value.togglePopup();
  }

  function flyTo(place, mapInstance) {
    const m = mapInstance || map.value;
    if (!m || !place) return;
    const lon = parseFloat(place.lon);
    const lat = parseFloat(place.lat);
    if (Number.isNaN(lon) || Number.isNaN(lat)) return;
    m.flyTo({ center: [lon, lat], zoom: 14, duration: 800 });
  }

  function fitBoundsToMarkers(mapInstance) {
    const m = mapInstance || map.value;
    if (!m) return;
    const bounds = new maplibregl.LngLatBounds();
    if (markerA.value) bounds.extend(markerA.value.getLngLat());
    if (markerB.value) bounds.extend(markerB.value.getLngLat());
    if (bounds.isEmpty()) return;
    m.fitBounds(bounds, { padding: 100, maxZoom: 14, duration: 600 });
  }

  async function initMap() {
    if (!containerRef.value) return;
    map.value = new maplibregl.Map({
      container: containerRef.value,
      style: tileserverStyleUrl.value,
      center: DEFAULT_CENTER,
      zoom: DEFAULT_ZOOM,
    });
    map.value.on('load', () => map.value.resize());
    return map.value;
  }

  onUnmounted(() => {
    clearResultMarkers();
    if (markerA.value) markerA.value.remove();
    if (markerB.value) markerB.value.remove();
    if (map.value) map.value.remove();
  });

  return { map, initMap, addResultMarkers, clearResultMarkers, setMarkerA, setMarkerB, flyTo, fitBoundsToMarkers };
}
