<script setup>
const props = defineProps({
    schema: Object,
    level: {
        type: Number,
        default: 0
    }
})
</script>

<template>
    <div class="space-y-2">
        
        <!-- FIELD -->
        <div
            v-if="schema?.type === 'field'"
            class="flex items-center gap-2 p-2 rounded-lg border border-white/10"
            :style="{ marginLeft: `${level * 16}px` }"
        >
            <span class="font-mono">
                {{ schema.key }}
            </span>

            <span class="opacity-60">
                →
            </span>

            <span class="opacity-80">
                {{ schema.data_type || 'any' }}
            </span>

            <span
                v-if="schema.required === false"
                class="text-xs opacity-50"
            >
                optional
            </span>
        </div>


        <!-- GROUP -->
        <div
            v-else-if="schema?.type === 'group'"
            class="space-y-2"
        >
            <div
                class="font-semibold opacity-80"
                :style="{ marginLeft: `${level * 16}px` }"
            >
                {{ schema.name || 'group' }}
            </div>

            <SchemaTree
                v-for="(field, i) in schema.fields || []"
                :key="i"
                :schema="field"
                :level="level + 1"
            />
        </div>


        <!-- ARRAY -->
        <div
            v-else-if="schema?.type === 'array'"
            class="space-y-2"
        >
            <div
                class="font-semibold opacity-80"
                :style="{ marginLeft: `${level * 16}px` }"
            >
                {{ schema.name }} []
            </div>

            <SchemaTree
                :schema="schema.items"
                :level="level + 1"
            />
        </div>

    </div>
</template>