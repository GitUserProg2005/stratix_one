<script setup>
const props = defineProps({
    tags: Array,
});

console.log('TAGS: ', props.tags);
</script>

<template>
    <!--Теги-->
    <div class="flex flex-wrap gap-1 w-98">
        <span v-for="tag in tags" 
            :key="tag.id"
            class="tag text-xs lg:text-base">#{{ tag.name }}</span>            
    </div>
</template>