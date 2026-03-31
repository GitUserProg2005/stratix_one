import { ref } from 'vue';
import axios from 'axios';

export function useRecoverOrder() {
  const order = ref(null);
  const driver = ref(null);

  async function recoverDataOrder() {
    const response = await axios.post(route('get.order'));
    if (response.data.success) {
      order.value = response.data.order;
      driver.value = response.data.driver ?? null;
    } else {
      order.value = null;
      driver.value = null;
    }
  }

  return { order, driver, recoverDataOrder };
}
