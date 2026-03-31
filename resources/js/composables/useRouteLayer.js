import { useMapServices } from '@/composables/useMapServices.js';

const ROUTE_SOURCE_ID = 'route';
const ROUTE_LAYER_ID = 'route-line';

function decodePolyline(encoded) {
  const coordinates = [];
  let index = 0;
  let lat = 0;
  let lng = 0;

  while (index < encoded.length) {
    let shift = 0;
    let result = 0;
    let byte;
    do {
      byte = encoded.charCodeAt(index++) - 63;
      result |= (byte & 0x1f) << shift;
      shift += 5;
    } while (byte >= 0x20);
    const dlat = result & 1 ? ~(result >> 1) : result >> 1;
    shift = 0;
    result = 0;
    do {
      byte = encoded.charCodeAt(index++) - 63;
      result |= (byte & 0x1f) << shift;
      shift += 5;
    } while (byte >= 0x20);
    const dlng = result & 1 ? ~(result >> 1) : result >> 1;
    lat += dlat;
    lng += dlng;
    coordinates.push([lng / 1e5, lat / 1e5]);
  }
  return coordinates;
}

function normalizeRouteGeometry(geometry) {
  if (!geometry) return null;
  if (typeof geometry === 'string') {
    const coordinates = decodePolyline(geometry);
    return coordinates.length ? { type: 'LineString', coordinates } : null;
  }
  if (geometry.type === 'LineString' && Array.isArray(geometry.coordinates) && geometry.coordinates.length) {
    return geometry;
  }
  return null;
}

export async function drawRoute(map, pointA, pointB, options = {}) {
  const { osrmUrl } = useMapServices();
  const base = (osrmUrl?.value ?? osrmUrl ?? 'http://localhost:5000').replace(/\/$/, '');
  const { color = '#3b82f6', sourceId = ROUTE_SOURCE_ID, layerId = ROUTE_LAYER_ID } = options;
  if (!map || !pointA || !pointB) return null;

  const lngA = pointA.lng ?? pointA.lon;
  const lngB = pointB.lng ?? pointB.lon;
  const latA = pointA.lat;
  const latB = pointB.lat;
  if (latA == null || latB == null || lngA == null || lngB == null) return null;

  const url = `${base}/route/v1/driving/${lngA},${latA};${lngB},${latB}?overview=full&geometries=geojson`;

  try {
    const res = await fetch(url);
    const data = await res.json();
    if (data.code !== 'Ok' || !data.routes?.length) return null;

    const route = data.routes[0];
    const geometry = normalizeRouteGeometry(route.geometry);
    if (!geometry) return null;

    const geojson = { type: 'Feature', properties: {}, geometry };
    if (!map.getSource(sourceId)) {
      map.addSource(sourceId, { type: 'geojson', data: geojson });
      map.addLayer({
        id: layerId,
        type: 'line',
        source: sourceId,
        layout: { 'line-join': 'round', 'line-cap': 'round' },
        paint: { 'line-color': color, 'line-width': 5 },
      });
    } else {
      map.getSource(sourceId).setData(geojson);
    }

    return { distance: route.distance ?? 0, duration: route.duration ?? 0 };
  } catch {
    return null;
  }
}

export function clearRoute(map, options = {}) {
  const sourceId = options.sourceId ?? ROUTE_SOURCE_ID;
  const layerId = options.layerId ?? ROUTE_LAYER_ID;
  if (!map) return;
  if (map.getLayer(layerId)) map.removeLayer(layerId);
  if (map.getSource(sourceId)) map.removeSource(sourceId);
}
