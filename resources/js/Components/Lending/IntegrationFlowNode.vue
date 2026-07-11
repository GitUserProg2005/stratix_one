<script setup>
import { Handle, Position } from '@vue-flow/core';

defineProps({
    data: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="integration-flow-node bg-content-glass rounded-2xl flex flex-col justify-center p-4 min-w-[8.5rem]">
        <Handle type="target" :position="Position.Left" class="integration-flow-handle" />

        <h4 class="text-sm font-semibold">{{ data.title }}</h4>
        <pre class="text-xs whitespace-pre-wrap">{{ data.desc }} <span class="text-green-700">{{ data.highlight }}</span></pre>

        <Handle type="source" :position="Position.Right" class="integration-flow-handle" />
    </div>
</template>
