const mistralCommonFields = [
    { name: 'api_key', label: 'API ключ Mistral', type: 'text', security: true, required: true },
    { name: 'temperature', label: 'Temperature', type: 'range', required: false, step: '0.1', min: 0, max: 2 },
    { name: 'max_tokens', label: 'Max tokens', type: 'number', required: false, min: 1 },
];

export const nodeConfigFields = {
    webhook_trigger: {
        builder: 'OutputBuilder',
        builder_root: 'request',
        builder_title: 'Настройка вебхука',
        buttons: [
            {
                label: 'URL-токен',
                key: 'copy_webhook_token',
                requiresLoading: false,
            }
        ],
    },
    ai_request: {
        fields: [
            { name: 'prompt', label: 'Ваш промпт', type: 'textarea', required: true },
        ],
        buttons: [],
    },
    ai_agent_request: {
        builder: 'OutputBuilder',
        builder_root: 'output',
        builder_title: 'Структура ответа',
        fields: [
            { name: 'prompt', label: 'Ваш agent-промпт', type: 'textarea', required: true },
        ],
        buttons: [],
    },
    email_report: {
        fields: [
            { name: 'email', label: 'Email получателя', type: 'text', required: true },
        ],
        buttons: [],
    },
    collect_metrics: {
        fields: [
            {
                name: 'project',
                label: 'ID проекта (заглушка)',
                type: 'text',
                required: false,
            },
        ],
        buttons: [],
    },
    update_metric: {
        builder: 'ConfigQueriesConfigure',
        builder_root: 'updatable_metrics',
        fields: [],
        buttons: []
    },
    condition: {
        builder: 'ConditionBuilder',
        builder_root: 'condition',
        builder_title: 'Структура условий',
        fields: [],
        buttons: [],
    },
    schedule: {
        fields: [
            { name: 'timing', label: 'Cron-выражение', type: 'text', required: true },
        ],
        buttons: [],
    },
    log: {
        fields: [
            { name: 'message', label: 'Метка в логе', type: 'text', required: false },
        ],
        buttons: [],
    },
    page_loader: {
        fields: [
            { name: 'url_source', label: 'URL страницы', type: 'text', required: true },
            {
                name: 'compression_level',
                label: 'Уровень сжатия',
                type: 'simple_select',
                required: true,
                options: [
                    { name: 'Script + Style', value: 'script_style' },
                    { name: 'Script + Style + Head + Footer + Nav + Sidebar', value: 'extended' },
                    { name: 'Только текст', value: 'text_only' },
                ],
            },
        ],
        buttons: [],
    },
    go_whisper: {
        fields: [],
        buttons: [],
    },
    mistral_text: {
        fields: [
            ...mistralCommonFields,
            { name: 'prompt', label: 'Промпт', type: 'textarea', required: true },
        ],
        buttons: [],
    },
    mistral_picture: {
        fields: [
            ...mistralCommonFields,
            { name: 'prompt', label: 'Промпт', type: 'textarea', required: true },
        ],
        buttons: [],
    },
    mistral_ocr: {
        fields: [
            ...mistralCommonFields,
        ],
        buttons: [],
    },
    point_in_polygon: {
        fields: [
            {
                name: 'polygon',
                label: 'Область проверки',
                customField: 'PointCheck',
                required: true,
            },
        ],
        buttons: [],
    },
};
