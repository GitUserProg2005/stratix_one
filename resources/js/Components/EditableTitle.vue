<script setup>
import { ref, nextTick } from 'vue';
import axios from 'axios';
import ToastrMessage from '@/Components/ToastrMessage.vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    playlistId: {
        type: Number,
        required: true
    },
    maxLength: {
        type: Number,
        default: 20
    }
});

const emit = defineEmits(['update:modelValue']);

const isEditing = ref(false);
const editValue = ref('');
const inputRef = ref(null);
const isSaving = ref(false);
const toast = ref({ isShow: false, title: '' });

function startEditing() {
    editValue.value = props.modelValue;
    isEditing.value = true;
    nextTick(() => inputRef.value?.focus());
}

function cancelEditing() {
    isEditing.value = false;
    editValue.value = '';
}

async function save() {
    const title = editValue.value?.trim() || props.modelValue;
    if (title === props.modelValue) {
        cancelEditing();
        return;
    }
    try {
        isSaving.value = true;
        const response = await axios.patch(
            route('playlist.update', props.playlistId),
            { title }
        );
        if (response.data.success && response.data.playlist) {
            emit('update:modelValue', response.data.playlist.title);
            toast.value = { isShow: true, title: 'Название плейлиста обновлено' };
        }
    } catch (e) {
        const msg = e.response?.data?.errors?.title?.[0] || 'Не удалось обновить название';
        toast.value = { isShow: true, title: msg };
    } finally {
        isSaving.value = false;
        cancelEditing();
    }
}

function onKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        save();
    }
    if (e.key === 'Escape') {
        cancelEditing();
    }
}
</script>

<template>
    <div class="w-full max-w-md">
        <h2
            v-show="!isEditing"
            class="extra-title text-white mt-1 cursor-pointer select-none hover:underline underline-offset-2"
            @dblclick="startEditing"
        >
            {{ modelValue }}
        </h2>
        <input
            v-show="isEditing"
            ref="inputRef"
            v-model="editValue"
            type="text"
            :maxlength="maxLength"
            class="extra-title search-input"
            :disabled="isSaving"
            @blur="save"
            @keydown="onKeydown"
        />
        
        <ToastrMessage :isShow="toast.isShow" :title="toast.title" />
    </div>
</template>
