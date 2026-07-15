export function resolveDynamicSchema(node, type, nodeSchema) {
    // VueFlow-нода: node.data.config | data из CustomNode: node.config
    const config = node?.data?.config ?? node?.config ?? null;

    if (!config || !nodeSchema) {
        return null;
    }

    const key = type === 'output'
        ? nodeSchema.dynamic_output_key
        : nodeSchema.dynamic_input_key;

    if (!key) {
        return null;
    }

    return config[key] ?? null;
}
