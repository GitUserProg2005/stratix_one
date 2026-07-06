export function buildAST(schema, mappings, prefix = '') {
    if (!schema) {
        return null;
    }

    const name = schema.name || schema.key || null;

    const path = name
        ? (prefix ? `${prefix}.${name}` : name)
        : prefix;

    if (schema.type === 'group') {
        const fields = (schema.fields || [])
            .map((field) => buildAST(field, mappings, path))
            .filter(Boolean);

        if (!fields.length) {
            return null;
        }

        return {
            type: 'group',
            name,
            fields,
        };
    }

    if (schema.type === 'array') {
        const from = mappings[path];
        const items = buildAST(schema.items, mappings, `${path}[]`);

        if (!from && !items) {
            return null;
        }

        return {
            type: 'array',
            name,
            ...(from ? { from } : {}),
            items: items || { type: 'group', name: null, fields: [] },
        };
    }

    if (schema.type === 'field') {
        const from = mappings[path];

        if (!from) {
            return null;
        }

        return {
            type: 'field',
            key: schema.key,
            from,
        };
    }

    return null;
}
