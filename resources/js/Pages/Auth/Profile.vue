<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch, onMounted } from 'vue';
import { usePage, router, Head, useForm } from '@inertiajs/vue3';
import Avatar from '@/Components/Avatar.vue';
import Back from '@/Components/BackButton.vue';
import Graph2D from '@/Pages/N8N/Components/Graph2D.vue';
import ProfileBackgroundSelector from '@/Pages/Auth/ProfileBackgroundSelector.vue';
import axios from 'axios';

const props = defineProps({
    user: Object,
    stats: {
        type: Object,
        default: () => ({ workflow_count: 0, dashboard_count: 0 }),
    },
    backgrounds: {
        type: Array,
        default: () => [],
    },
    selected_background_id: {
        type: [Number, null],
        default: null,
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);
const isOwnProfile = computed(() => currentUser.value && currentUser.value.id === props.user.id);

const backgroundError = ref(false);
const avatarPreview = ref(null);
const backgroundPreview = ref(null);
const avatarInput = ref(null);
const backgroundInput = ref(null);

const form = useForm({
    avatar: null,
    background: null,
});

const workflowClusters = ref([]);
const isGraphLoading = ref(false);

const displayAvatarUrl = computed(() => avatarPreview.value || props.user.avatar_url);
const displayBackgroundUrl = computed(() => backgroundPreview.value || props.user.background_url);

const coverStyle = computed(() => {
    if (!displayBackgroundUrl.value || backgroundError.value) {
        return null;
    }
    return { backgroundImage: `url(${displayBackgroundUrl.value})` };
});

watch(() => props.user.avatar_url, () => {
    if (avatarPreview.value) {
        URL.revokeObjectURL(avatarPreview.value);
        avatarPreview.value = null;
    }
});

watch(() => props.user.background_url, () => {
    if (backgroundPreview.value) {
        URL.revokeObjectURL(backgroundPreview.value);
        backgroundPreview.value = null;
    }
    backgroundError.value = false;
});

function onAvatarSelected(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }
    form.background = null;
    form.avatar = file;
    if (avatarPreview.value) {
        URL.revokeObjectURL(avatarPreview.value);
    }
    avatarPreview.value = URL.createObjectURL(file);
    saveMedia();
}

function saveMedia() {
    form.post(route('profile.media'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onFinish: () => {
            if (avatarInput.value) {
                avatarInput.value.value = '';
            }
            if (backgroundInput.value) {
                backgroundInput.value.value = '';
            }
        },
    });
}

function openBackgroundPicker() {
    backgroundInput.value?.click();
}

function openAvatarPicker() {
    avatarInput.value?.click();
}

function onBackgroundSelected(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }
    form.avatar = null;
    form.background = file;
    if (backgroundPreview.value) {
        URL.revokeObjectURL(backgroundPreview.value);
    }
    backgroundPreview.value = URL.createObjectURL(file);
    backgroundError.value = false;
    saveMedia();
}

function logout() {
    router.post(route('logout'));
}

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('dashboard'));
    }
}

async function loadWorkflowGraph() {
    isGraphLoading.value = true;

    try {
        const response = await axios.get(route('profile.workflow-graph', props.user.id));

        if (response.data.result === 'ok') {
            workflowClusters.value = response.data.clusters ?? [];
        }
    } catch (error) {
        console.error('Ошибка загрузки графа workflow:', error);
    } finally {
        isGraphLoading.value = false;
    }
}

onMounted(() => {
    loadWorkflowGraph();
});
</script>

<template>
    <Head :title="isOwnProfile ? 'Профиль' : user.name" />

    <AppLayout>
        <div class="w-full min-h-screen">
            <div class="px-4 py-6">
                <div
                    v-if="!isOwnProfile"
                    class="sticky top-0 z-30 pt-4 pb-2 bg-content-glass"
                >
                    <Back @back="goBack" />
                </div>

                <div
                    class="relative h-40 sm:h-48 bg-cover bg-center bg-no-repeat rounded-3xl group"
                    :class="[
                        !coverStyle ? 'bg-gradient-to-r from-black via-orange-500 to-indigo-500' : '',
                        isOwnProfile ? 'cursor-pointer' : '',
                    ]"
                    :style="coverStyle"
                    @click="isOwnProfile && openBackgroundPicker()"
                >
                    <img
                        v-if="displayBackgroundUrl"
                        :src="displayBackgroundUrl"
                        alt=""
                        class="sr-only"
                        @error="backgroundError = true"
                    />

                    <div
                        v-if="isOwnProfile"
                        class="absolute inset-0 flex items-center justify-center rounded-3xl bg-black/0 group-hover:bg-black/30 transition-colors"
                    >
                        <span
                            class="opacity-0 group-hover:opacity-100 transition-opacity text-white text-sm font-semibold flex items-center gap-2"
                        >
                            <i class="fa-solid fa-camera" />
                            {{ form.processing && form.background ? 'Сохранение...' : 'Сменить фон' }}
                        </span>
                    </div>
                </div>

                <input
                    v-if="isOwnProfile"
                    ref="backgroundInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    class="hidden"
                    @change="onBackgroundSelected"
                />

                <div class="relative z-10 -mt-14 sm:-mt-16 flex flex-col items-start gap-4 lg:flex-row lg:items-end lg:justify-between w-full">
                    <div class="flex items-end gap-4">
                        <button
                            v-if="isOwnProfile"
                            type="button"
                            class="group/avatar relative inline-flex shrink-0 rounded-full ring-4 ring-[var(--accent)] disabled:opacity-60"
                            :disabled="form.processing"
                            @click="openAvatarPicker"
                        >
                            <Avatar
                                :name="user.name"
                                :src="displayAvatarUrl"
                                :userId="user.id"
                                :no-link="true"
                                size="xl"
                            />
                            <span
                                class="absolute inset-0 flex items-center justify-center rounded-full bg-black/0 group-hover/avatar:bg-black/30 transition-colors"
                            >
                                <i class="fa-solid fa-camera text-white text-sm opacity-0 group-hover/avatar:opacity-100 transition-opacity" />
                            </span>
                        </button>

                        <div
                            v-else
                            class="inline-flex shrink-0 rounded-full ring-4 ring-[var(--accent)]"
                        >
                            <Avatar
                                :name="user.name"
                                :src="displayAvatarUrl"
                                :userId="user.id"
                                size="xl"
                            />
                        </div>

                        <input
                            v-if="isOwnProfile"
                            ref="avatarInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            class="hidden"
                            @change="onAvatarSelected"
                        />

                        <div class="min-w-0">
                            <h1 class="title text-xl sm:text-2xl t-color-content">{{ user.name }}</h1>
                            <p
                                v-if="user.email && isOwnProfile"
                                class="context text-sm mt-1 break-all"
                            >
                                {{ user.email }}
                            </p>
                            <p
                                v-if="isOwnProfile && form.processing"
                                class="context text-xs mt-1 t-color-primary"
                            >
                                Сохранение...
                            </p>
                        </div>
                    </div>
                </div>

                <p
                    v-if="isOwnProfile && (form.errors.avatar || form.errors.background)"
                    class="context text-sm t-color-danger mt-4"
                >
                    {{ form.errors.avatar || form.errors.background }}
                </p>

                <section class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="bg-glass-frost p-4 rounded-2xl">
                        <span class="t-color-secondary title-2">Воркфлоу</span> <br>
                        <span class="title-font t-color-primary">{{ stats.workflow_count }}</span>
                    </div>

                    <div class="bg-glass-frost p-4 rounded-2xl">
                        <span class="t-color-secondary title-2">Дашборды</span> <br>
                        <span class="title-font t-color-primary">{{ stats.dashboard_count }}</span>
                    </div>
                </section>

                <ProfileBackgroundSelector
                    v-if="isOwnProfile"
                    :backgrounds="backgrounds"
                    :model-value="selected_background_id"
                />

                <section class="mt-6">
                    <h2 class="title-2 mb-3 flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-diagram-project text-[var(--accent)]" />
                        Карта автоматизаций
                    </h2>

                    <div
                        v-if="isGraphLoading"
                        class="dashboard-inset flex h-72 items-center justify-center rounded-2xl"
                    >
                        <span class="context text-sm opacity-70">Загрузка графа...</span>
                    </div>

                    <Graph2D
                        v-else
                        :clusters="workflowClusters"
                        class="h-72 w-full"
                    />
                </section>

                <button
                    v-if="isOwnProfile"
                    type="button"
                    class="primary-btn mt-6 inline-flex items-center gap-2"
                    @click="logout"
                >
                    Выйти
                    <i class="fa-solid fa-right-from-bracket text-xs" />
                </button>
            </div>
        </div>
    </AppLayout>
</template>
