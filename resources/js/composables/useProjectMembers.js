import { ref } from 'vue';
import axios from 'axios';

export function useProjectMembers() {
    const addedUsers = ref([]);
    const searchRef = ref(null);

    async function searchUsers(query, insideProjectId = null) {
        // 1. Ищем юзеров на бэке
        const { data } = await axios.get(route('projects.users.search'), {
            params: {
                query,
                ...(insideProjectId ? { inside_project_id: insideProjectId } : {}),
            },
        });

        // 2. Уже добавленных прячем
        const addedIds = new Set(addedUsers.value.map((u) => u.id));

        return data.filter((u) => !addedIds.has(u.id));
    }

    function addUser(user) {
        if (!user?.id) return;
        if (addedUsers.value.some((u) => u.id === user.id)) return;

        addedUsers.value.push(user);
        searchRef.value?.clearResults();
    }

    function removeUser(user) {
        addedUsers.value = addedUsers.value.filter((u) => u.id !== user.id);
    }

    function setMembers(users) {
        addedUsers.value = [...users];
    }

    function clearMembers() {
        addedUsers.value = [];
        searchRef.value?.clearResults();
    }

    return {
        addedUsers,
        searchRef,
        searchUsers,
        addUser,
        removeUser,
        setMembers,
        clearMembers,
    };
}
