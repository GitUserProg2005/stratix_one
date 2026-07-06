export function resolveInputSchema(nodeSchema, nodeConfig) {
    if (!nodeSchema) {
        return null;
    }

    const mode = nodeConfig?.mode;

    if (mode && nodeSchema.inputSchemaModes?.[mode]) {
        return nodeSchema.inputSchemaModes[mode];
    }

    return nodeSchema.inputSchema ?? null;
}
