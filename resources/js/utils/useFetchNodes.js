import { ref } from 'vue';
import axios from 'axios';

export async function fetchNodes(workflowId) {
    const nodes = ref([]);

    const response = await axios.get(route('get.nodes', workflowId));

    if (response.data.result === 'ok') {
        nodes.value = response.data.nodes;
    }

    return { nodes };
}
