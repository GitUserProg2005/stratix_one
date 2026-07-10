import { resolveDynamicSchema } from './resolveDynamicSchema';

export function resolveInputSchema(nodeSchema, nodeConfig, node = null) {
    if (!nodeSchema) {
        return null;
    }

    const mode = nodeConfig?.mode;

    if (mode && nodeSchema.inputSchemaModes?.[mode]) {
        return nodeSchema.inputSchemaModes[mode];
    }

    if (nodeSchema.dynamic_input) {
        return resolveDynamicSchema(node, 'input', nodeSchema);
    }

    return nodeSchema.inputSchema ?? null;
}
