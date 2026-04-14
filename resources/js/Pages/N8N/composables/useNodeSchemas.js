import { ref, onMounted } from 'vue';
import axios from 'axios';


export function useNodeSchemas() {
    const schemas = ref({});

    onMounted(async () => {
        try {
            const response = await axios.get(route('get.node.schemas'));
            
            if (response.data.result === 'ok') {
                schemas.value = response.data.schemas;
                console.log('Node schemas initialized.', schemas.value);
            }
        } catch (error) {
            console.error('Error fetching node schemas:', error);
        }
    }); 

    return { schemas };
}