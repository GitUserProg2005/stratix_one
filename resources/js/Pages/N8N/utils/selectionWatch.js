import { watch } from "vue";
import axios from "axios";
import { nodeConfigFields } from "../Components/nodeConfigFields";

export function useNodeTypeWatcher({
    nodeType,
    config,
    backendOptions,
    workflowId = null,
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

            // Загрузка опций для backend_request
            for (const field of fields) {
                if (!field.backend_request) continue

                try {
                    const req = field.backend_request
                    const routeConfig = req.route
                    let routeName = null
                    let params = []

                    // Строка или объект { name, zoomable }
                    if (typeof routeConfig === 'string') {
                        routeName = routeConfig
                    } else if (routeConfig && typeof routeConfig === 'object') {
                        routeName = routeConfig.name ?? null
                        if (routeConfig.zoomable) {
                            const id = typeof workflowId === 'function' ? workflowId() : workflowId
                            params = id ? [id] : []
                        }
                    }

                    if (!routeName) continue

                    const res = await axios.get(route(routeName, ...params))

                    const map = req.response_map
                    const items = map ? (res.data?.[map.root] || []) : []

                    backendOptions.value[field.name] = items.map(item => ({
                        label: item[map.label],
                        value: item[map.value],
                    }))
                } catch (e) {
                    console.error(
                        `Ошибка загрузки опций для поля ${field.name}`,
                        e
                    )
                    backendOptions.value[field.name] = []
                }
            }
        },
        { immediate }
    )
}
