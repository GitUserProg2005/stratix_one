import { ref, onMounted } from 'vue';
import axios from 'axios';


export function useNodeSchemas() {
    const schemas = ref({});
    const isLoading = ref(true);

    onMounted(async () => {
        isLoading.value = true;
        try {
            const response = await axios.get(route('get.node.schemas'));

            if (response.data.result === 'ok') {
                schemas.value = response.data.schemas;
            }
        } catch (error) {
            console.error('Error fetching node schemas:', error);
        } finally {
            isLoading.value = false;
        }
    });

    return { schemas, isLoading };
}