export function isValidSchema(schema) {
    return Boolean(schema && typeof schema === 'object' && !Array.isArray(schema) && schema.type);
}
