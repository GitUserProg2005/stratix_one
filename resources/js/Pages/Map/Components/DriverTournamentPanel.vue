<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, ref } from 'vue';
import { useUpdateCarPosition } from '@/composables/useUpdateCarPosition';

const props = defineProps({
  map: { type: Object, default: null },
  user: { type: Object, default: null },
});

const mapRef = computed(() => (props.map?.value ?? props.map));
const { driverMovementAnimation } = useUpdateCarPosition({ mapRef });

const loading = ref(false);
const tournament = ref(null);
const participants = ref([]);
const joined = ref(false);
const simRunning = ref(false);
const simulatedPoints = new Map();
const pointCollections = new Map();
let simTimer = null;
let tournamentChannel = null;

const driverColors = {
  2: '#f59e0b',
  3: '#10b981',
};

const routes = {
  2: [
    { lat: 55.7383506, lng: 37.6169827 }, // Yakimanka start
    { lat: 55.7393000, lng: 37.6191000 },
    { lat: 55.7409000, lng: 37.6205000 },
    { lat: 55.7414000, lng: 37.6179000 },
    { lat: 55.7397000, lng: 37.6156000 },
    { lat: 55.7386000, lng: 37.6149000 },
  ],
  3: [
    { lat: 55.7383506, lng: 37.6169827 }, // Yakimanka start
    { lat: 55.7391000, lng: 37.6148000 },
    { lat: 55.7407000, lng: 37.6163000 },
    { lat: 55.7411000, lng: 37.6194000 },
    { lat: 55.7395000, lng: 37.6209000 },
    { lat: 55.7388000, lng: 37.6182000 },
  ],
};

function sourceId(driverId) {
  return `tournament-territory-${driverId}`;
}

function fillLayerId(driverId) {
  return `tournament-territory-fill-${driverId}`;
}

function lineLayerId(driverId) {
  return `tournament-territory-line-${driverId}`;
}

function pointsSourceId(driverId) {
  return `tournament-points-${driverId}`;
}

function pointsLayerId(driverId) {
  return `tournament-points-layer-${driverId}`;
}

function addDriverPoint(driverId, lat, lng) {
  const map = mapRef.value;
  if (!map || Number.isNaN(Number(lat)) || Number.isNaN(Number(lng))) return;

  if (!pointCollections.has(driverId)) {
    pointCollections.set(driverId, {
      type: 'FeatureCollection',
      features: [],
    });
  }

  const collection = pointCollections.get(driverId);
  collection.features.push({
    type: 'Feature',
    properties: { driver_id: driverId },
    geometry: {
      type: 'Point',
      coordinates: [Number(lng), Number(lat)],
    },
  });

  const sid = pointsSourceId(driverId);
  const lid = pointsLayerId(driverId);
  const color = driverColors[driverId] ?? '#3b82f6';

  if (map.getSource(sid)) {
    map.getSource(sid).setData(collection);
  } else {
    map.addSource(sid, { type: 'geojson', data: collection });
    map.addLayer({
      id: lid,
      type: 'circle',
      source: sid,
      paint: {
        'circle-radius': 4,
        'circle-color': color,
        'circle-stroke-color': '#ffffff',
        'circle-stroke-width': 1.2,
      },
    });
  }
}

function upsertTerritory(driverId, geojsonString) {
  const map = mapRef.value;
  if (!map || !geojsonString) return;

  let data = null;
  try {
    data = JSON.parse(geojsonString);
  } catch {
    return;
  }
  if (!data || (data.type !== 'Polygon' && data.type !== 'MultiPolygon')) return;

  const featureData = { type: 'Feature', geometry: data, properties: { driver_id: driverId } };
  const sid = sourceId(driverId);
  const fillId = fillLayerId(driverId);
  const lineId = lineLayerId(driverId);
  const color = driverColors[driverId] ?? '#3b82f6';

  if (map.getSource(sid)) {
    map.getSource(sid).setData(featureData);
  } else {
    map.addSource(sid, { type: 'geojson', data: featureData });
    map.addLayer({
      id: fillId,
      type: 'fill',
      source: sid,
      paint: {
        'fill-color': color,
        'fill-opacity': 0.82,
      },
    });
    map.addLayer({
      id: lineId,
      type: 'line',
      source: sid,
      paint: {
        'line-color': color,
        'line-width': 2,
      },
    });
  }
}

function subscribeTournamentChannel() {
  if (!tournament.value?.id || typeof window.Echo === 'undefined') return;
  unsubscribeTournamentChannel();
  const channelName = `tournament.${tournament.value.id}`;
  tournamentChannel = window.Echo.private(channelName)
    .listen('.DriverPolygonUpdated', (event) => {
      upsertTerritory(Number(event.driverId ?? event.driver_id), event.geojson);
    })
    .listen('.DriverPointsAdded', (event) => {
      const driverId = Number(event.driverId ?? event.driver_id);
      addDriverPoint(driverId, event.lat, event.lng);
      driverMovementAnimation(driverId, { lat: event.lat, lng: event.lng }, mapRef);
    });
}

function unsubscribeTournamentChannel() {
  if (!tournament.value?.id || typeof window.Echo === 'undefined') return;
  const channelName = `tournament.${tournament.value.id}`;
  window.Echo.leave(channelName);
  tournamentChannel = null;
}

async function loadTournament() {
  loading.value = true;
  try {
    const { data } = await axios.get(route('tournaments.active'));
    tournament.value = data.tournament;
    participants.value = data.participants ?? [];
    (data.territories ?? []).forEach((t) => upsertTerritory(t.driver_id, t.geojson));
    joined.value = participants.value.some((p) => Number(p.id) === Number(props.user?.id));
    if (joined.value) subscribeTournamentChannel();
  } finally {
    loading.value = false;
  }
}

async function joinTournament() {
  if (!tournament.value?.id) return;
  await axios.post(route('tournaments.join', tournament.value.id), { driver_id: props.user?.id });
  const { data } = await axios.get(route('tournaments.state', tournament.value.id));
  participants.value = data.participants ?? [];
  (data.territories ?? []).forEach((t) => upsertTerritory(t.driver_id, t.geojson));
  joined.value = true;
  subscribeTournamentChannel();
}

async function sendPosition(tournamentId, driverId, point) {
  await axios.post(route('tournaments.update-position', tournamentId), {
    driver_id: driverId,
    lat: point.lat,
    lng: point.lng,
  });
  addDriverPoint(driverId, point.lat, point.lng);
  driverMovementAnimation(driverId, point, mapRef);
}

function startSimulation() {
  if (!joined.value || !tournament.value?.id || simRunning.value) return;
  subscribeTournamentChannel();
  simulatedPoints.clear();
  pointCollections.clear();
  Object.keys(routes).forEach((id) => simulatedPoints.set(Number(id), 0));
  simRunning.value = true;

  const primeDrivers = async () => {
    for (const idRaw of Object.keys(routes)) {
      const driverId = Number(idRaw);
      try {
        await axios.post(route('tournaments.join', tournament.value.id), { driver_id: driverId });
      } catch {
        // continue: one driver failure should not block others
      }
    }
  };

  primeDrivers();

  simTimer = setInterval(async () => {
    const tid = tournament.value.id;
    for (const [idRaw, points] of Object.entries(routes)) {
      const driverId = Number(idRaw);
      const idx = simulatedPoints.get(driverId) ?? 0;
      const point = points[idx % points.length];
      simulatedPoints.set(driverId, idx + 1);
      try {
        await sendPosition(tid, driverId, point);
      } catch {
        // keep simulation running even with temporary request errors
      }
    }
  }, 3000);
}

function stopSimulation() {
  if (simTimer) clearInterval(simTimer);
  simTimer = null;
  simRunning.value = false;
}

loadTournament();

onBeforeUnmount(() => {
  stopSimulation();
  unsubscribeTournamentChannel();
});
</script>

<template>
  <section class="mb-6 rounded-2xl border border-black/10 dark:border-white/10 bg-content-outline p-3">
    <p class="t-body font-semibold">Турнир водителей</p>
    <p v-if="tournament" class="context mt-1">
      {{ tournament.name }} · приз {{ tournament.prize_d_coins }} DCoins
    </p>
    <p v-if="!tournament && !loading" class="context mt-1">Турнир не найден</p>

    <div class="mt-3 flex flex-wrap gap-2">
      <button
        v-if="tournament && !joined"
        type="button"
        class="primary-btn px-3 py-1.5 rounded text-xs"
        @click="joinTournament"
      >
        Присоединиться
      </button>
      <button
        v-if="tournament && joined && !simRunning"
        type="button"
        class="primary-btn px-3 py-1.5 rounded text-xs"
        @click="startSimulation"
      >
        Старт имитации DRIVER ID 2, 3
      </button>
      <button
        v-if="simRunning"
        type="button"
        class="primary-btn-white-blur px-3 py-1.5 rounded text-xs"
        @click="stopSimulation"
      >
        Стоп имитации
      </button>
    </div>

    <p class="context mt-2">
      Участники: {{ participants.map((p) => p.id).join(', ') || '—' }}
    </p>
  </section>
</template>

