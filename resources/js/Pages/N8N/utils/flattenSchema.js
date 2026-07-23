function resolvePrefix(prefix, name) {
    if (!name) {
        return prefix;
    }

    return prefix ? `${prefix}.${name}` : name;
}

export function flattenSchema(schema, prefix = '') {
    const result = [];

    if (!schema) {
        return result;
    }

    const name = schema.name || schema.key;

    if (schema.type === 'field') {
        const path = resolvePrefix(prefix, name);

        if (path) {
            result.push(path);
        }
    }

    if (schema.type === 'group') {
        const newPrefix = resolvePrefix(prefix, name);

        for (const field of schema.fields || []) {
            result.push(...flattenSchema(field, newPrefix));
        }
    }

    if (schema.type === 'array') {
        const arrayPath = resolvePrefix(prefix, name);

        // путь самого массива — для маппинга agents → data.agents
        if (arrayPath) {
            result.push(arrayPath);
        }

        const itemPrefix = arrayPath ? `${arrayPath}[]` : `${name}[]`;
        result.push(...flattenSchema(schema.items, itemPrefix));
    }

    return result;
}

// Только пути массивов из output-схемы источника
export function flattenArrayPaths(schema, prefix = '') {
    const result = [];

    if (!schema) {
        return result;
    }

    const name = schema.name || schema.key;

    if (schema.type === 'group') {
        const newPrefix = resolvePrefix(prefix, name);

        for (const field of schema.fields || []) {
            result.push(...flattenArrayPaths(field, newPrefix));
        }
    }

    if (schema.type === 'array') {
        const arrayPath = resolvePrefix(prefix, name);

        if (arrayPath) {
            result.push(arrayPath);
        }
    }

    return result;
}
