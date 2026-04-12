import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const DEFAULTS = {
  tileserverUrl: 'http://localhost:8082',
  osrmUrl: 'http://localhost:5000',
  tileserverStyle: 'bright',
};

/**
 * Единая точка конфигурации URL картографических сервисов.
 * Значения задаются в .env (TILESERVER_URL, OSRM_URL, TILESERVER_STYLE) и передаются
 * с бэкенда через Inertia (HandleInertiaRequests → mapServices).
 */
export function useMapServices() {
  const page = usePage();
  const mapServices = computed(() => page.props?.mapServices ?? {});

  const tileserverUrl = computed(() => {
    const base = mapServices.value.tileserverUrl ?? DEFAULTS.tileserverUrl;
    return base.replace(/\/$/, '');
  });

  const osrmUrl = computed(() => {
    const base = mapServices.value.osrmUrl ?? DEFAULTS.osrmUrl;
    return base.replace(/\/$/, '');
  });

  const tileserverStyle = computed(() => mapServices.value.tileserverStyle ?? DEFAULTS.tileserverStyle);

  const tileserverStyleUrl = computed(() => {
    const base = tileserverUrl.value;
    const style = tileserverStyle.value;
    return `${base}/styles/${style}/style.json`;
  });

  return {
    tileserverUrl,
    osrmUrl,
    tileserverStyle,
    tileserverStyleUrl,
  };
}
