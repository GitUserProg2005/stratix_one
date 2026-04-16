export function flattenSchema(schema, prefix = '') {
    let result = [];

    if (!schema) return result;

    const name = schema.name || schema.key;

    if (schema.type === 'group') {

        const newPrefix = prefix
            ? `${prefix}.${name}`
            : name;

        schema.fields?.forEach(field => {
            result.push(...flattenSchema(field, newPrefix));
        });
    }

    if (schema.type === 'array') {

        const newPrefix = prefix
            ? `${prefix}.${name}[]`
            : `${name}[]`;

        result.push(...flattenSchema(schema.items, newPrefix));
    }

    if (schema.type === 'field') {

        const path = prefix
            ? `${prefix}.${name}`
            : name;

        result.push(path);
    }

    return result;
}