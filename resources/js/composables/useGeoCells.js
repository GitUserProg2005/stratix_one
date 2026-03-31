import { ref } from 'vue';
import ngeohash from 'ngeohash';

export function useGeoCells(options = {}) {
  const { drawDriverMarker, driverMovementAnimation, mapRef, onDriverCoordsUpdated, onOrderCreated, currentUserId } = options;
  const currentCells = ref([]);
  const subscribedCells = ref([]);

  function getCells(lat, lng) {
    const cell = ngeohash.encode(lat, lng, 6);
    const neighbors = ngeohash.neighbors(cell);
    return [cell, ...neighbors];
  }

  function subscribeToCells(cells) {
    if (typeof window.Echo === 'undefined') {
      subscribedCells.value = cells;
      return;
    }

    cells.forEach((cell) => {
      if (drawDriverMarker || driverMovementAnimation || onDriverCoordsUpdated) {
        window.Echo.private(`drivers.${cell}`).listen('.DriverLocationUpdated', (e) => {
          const driver = e.driver;
          if (!driver || driver.id == null) return;
          if (currentUserId != null && Number(driver.id) === Number(currentUserId)) return;
          const lat = parseFloat(driver.lat);
          const lng = parseFloat(driver.lng);
          if (Number.isNaN(lat) || Number.isNaN(lng)) return;
          const mapInstance = mapRef?.value ?? mapRef;
          if (driverMovementAnimation && mapInstance) {
            driverMovementAnimation(driver.id, { lat, lng }, mapInstance);
          } else if (drawDriverMarker && mapInstance) {
            drawDriverMarker(driver, mapInstance);
          }
          if (onDriverCoordsUpdated) onDriverCoordsUpdated(driver);
        });
      }

      if (typeof onOrderCreated === 'function') {
        window.Echo.private(`order-cells.${cell}`).listen('.order.created', (payload) => {
          onOrderCreated(payload);
        });
      }
    });

    subscribedCells.value = cells;
  }

  function unsubscribeFromCells() {
    if (typeof window.Echo === 'undefined') {
      subscribedCells.value = [];
      currentCells.value = [];
      return;
    }

    subscribedCells.value.forEach((cell) => {
      if (drawDriverMarker || driverMovementAnimation || onDriverCoordsUpdated) {
        window.Echo.leave(`drivers.${cell}`);
      }
      if (typeof onOrderCreated === 'function') {
        window.Echo.leave(`order-cells.${cell}`);
      }
    });
    subscribedCells.value = [];
  }

  function updateCurrentCells(lat, lng) {
    const cells = getCells(lat, lng);
    const mainCell = cells[0];
    if (mainCell === currentCells.value[0]) return;
    unsubscribeFromCells();
    subscribeToCells(cells);
    currentCells.value = cells;
  }

  return { getCells, updateCurrentCells, unsubscribeFromCells, currentCells, subscribedCells };
}
