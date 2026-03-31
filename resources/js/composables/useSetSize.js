import { ref, onUnmounted } from 'vue';

export function useSetSize(containerRef) {
  const height = ref(0);
  let startY = 0;
  let startHeight = 0;

  function startResize(event) {
    if (!containerRef.value) return;
    event.preventDefault();
    const clientY = event.clientY ?? event.touches?.[0]?.clientY ?? 0;
    startY = clientY;
    startHeight = containerRef.value.offsetHeight;
    window.addEventListener('mousemove', setYSize);
    window.addEventListener('mouseup', stopResize);
    window.addEventListener('touchmove', setYSize, { passive: false });
    window.addEventListener('touchend', stopResize);
  }

  function setYSize(event) {
    if (!containerRef.value) return;
    event.preventDefault();
    const clientY = event.clientY ?? event.touches?.[0]?.clientY ?? 0;
    const delta = startY - clientY;
    height.value = Math.max(200, Math.min(startHeight, startHeight + delta));
  }

  function stopResize() {
    window.removeEventListener('mousemove', setYSize);
    window.removeEventListener('mouseup', stopResize);
    window.removeEventListener('touchmove', setYSize);
    window.removeEventListener('touchend', stopResize);
  }

  onUnmounted(stopResize);
  return { height, startResize };
}
