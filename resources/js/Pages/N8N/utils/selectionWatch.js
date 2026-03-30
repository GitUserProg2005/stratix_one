import { watch } from "vue";
import axios from "axios";
import { nodeConfigFields } from "../Components/nodeConfigFields";

export function useNodeTypeWatcher({
    nodeType,
    config,
    backendOptions,
    immediate = true,
    resetConfigOnChange = true,
}) {
    watch(
        () => nodeType.value,
        async (newType, oldType) => {
            if (!newType) return

            // Сброс конфига при смене типа
            if (resetConfigOnChange && newType !== oldType) {
                config.value = {}
            }

            const fields = nodeConfigFields[newType]?.fields ?? []

            for (const field of fields) {
                if (!field.backend_request) continue

                try {
                    const res = await axios.get(
                        route(field.backend_request.route)
                    )

                    const map = field.backend_request.response_map
                    const items = res.data?.[map.root] || []

                    backendOptions.value[field.name] = items.map(item => ({
                        label: item[map.label],
                        value: item[map.value],
                    }))
                } catch (e) {
                    console.error(
                        `Ошибка загрузки опций для поля ${field.name}`,
                        e
                    )
                }
            }
        },
        { immediate }
    )
}