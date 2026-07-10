export function resolveDynamicSchema(node, type, nodeSchema) {
    if (!node?.data?.config || !nodeSchema) {
        return null;
    }

    const key = type === 'output'
        ? nodeSchema.dynamic_output_key
        : nodeSchema.dynamic_input_key;

    if (!key) {
        return null;
    }

    return node.data.config[key] ?? null;
}
