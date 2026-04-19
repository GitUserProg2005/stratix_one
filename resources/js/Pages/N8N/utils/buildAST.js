export function buildAST(schema, mappings, prefix='') {
    if (!schema) return null;

    const name = schema.name || schema.key;

    const path = prefix
        ? `${prefix}.${name}`
        : name;

    if (schema.type === 'group') {
        return {
            type: 'group',
            name: name || null,
            fields: schema.fields
                .map(field => buildAST(field, mappings, path))
                .filter(Boolean)
        };
    }

    if (schema.type === 'array') {
        return {
            type: 'array',
            name: name,
            items: buildAST(schema.items, mappings, path + '[]')
        };
    }

    if (schema.type === 'field') {
        const from = mappings[path];

        if (!from) return null;

        return {
            type: 'field',
            key: schema.key,
            from
        };
    }

    return null;
}