export const nodeConfigFields = {
    webhook_trigger: {
        builder: 'OutputBuilder',
        builder_root: 'request',
        builder_title: 'Настройка вебхука',
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
    condition: {
        builder: 'ConditionBuilder',
        builder_root: 'condition',
        builder_title: 'Структура условий',
        fields: [],
        buttons: [],
    },
    log: {
        fields: [
            { name: 'message', label: 'Метка в логе', type: 'text', required: false },
        ],
        buttons: [],
    },
};
