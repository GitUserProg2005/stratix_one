<?php

namespace Database\Seeders\Support;

class WorkflowSeedData
{
    public static function users(): array
    {
        return [
            'test1@gmail.com' => ['name' => 'Test User 1'],
            'test2@gmail.com' => ['name' => 'Test User 2'],
        ];
    }

    /**
     * @return list<array{
     *     name: string,
     *     user_email: string,
     *     meta: array<string, mixed>|null,
     *     nodes: list<array{
     *         key: string,
     *         type: string,
     *         order: int,
     *         title: string,
     *         config: array<string, mixed>,
     *         position: array{x: int|float, y: int|float},
     *         condition_branches?: array{true: string, false: string}
     *     }>,
     *     edges: list<array{source: string, target: string, label?: string|null, type?: string}>
     * }>
     */
    public static function workflows(): array
    {
        return [
            // --- test1@gmail.com (10 workflows) ---
            [
                'name' => 'AI-ответ на заявку с сайта',
                'user_email' => 'test1@gmail.com',
                'meta' => ['tags' => ['ai', 'support', 'webhook']],
                'nodes' => [
                    self::webhookNode('trigger', 1, 'Вебхук заявки', 100, 120, 'lead', [
                        ['key' => 'name', 'data_type' => 'string'],
                        ['key' => 'email', 'data_type' => 'string'],
                        ['key' => 'message', 'data_type' => 'string'],
                    ]),
                    self::fieldNode('ai_reply', 'ai_request', 2, 'GigaChat ответ', 380, 120, [
                        'prompt' => 'Ты оператор поддержки. Ответь клиенту кратко и вежливо на его обращение.',
                    ]),
                    self::fieldNode('send_mail', 'email_report', 3, 'Отправка на email', 660, 120, [
                        'email' => 'support@example.com',
                    ]),
                ],
                'edges' => [
                    ['source' => 'trigger', 'target' => 'ai_reply'],
                    ['source' => 'ai_reply', 'target' => 'send_mail'],
                ],
            ],
            [
                'name' => 'Ежедневный дайджест новостей',
                'user_email' => 'test1@gmail.com',
                'meta' => ['tags' => ['schedule', 'scraper', 'ai']],
                'nodes' => [
                    self::fieldNode('cron', 'schedule', 1, 'Каждое утро 9:00', 100, 100, [
                        'timing' => '0 9 * * *',
                    ]),
                    self::fieldNode('load_page', 'page_loader', 2, 'Загрузка ленты', 380, 100, [
                        'url_source' => 'https://news.ycombinator.com',
                        'compression_level' => 'text_only',
                    ]),
                    self::agentNode('summarize', 3, 'AI-дайджест', 660, 100, 'Сделай краткий дайджест главных тем из текста страницы.', 'digest', [
                        ['key' => 'headline', 'data_type' => 'string'],
                        ['key' => 'items', 'data_type' => 'string'],
                    ]),
                    self::fieldNode('log_done', 'log', 4, 'Лог выполнения', 940, 100, [
                        'message' => 'Дайджест сформирован',
                    ]),
                ],
                'edges' => [
                    ['source' => 'cron', 'target' => 'load_page'],
                    ['source' => 'load_page', 'target' => 'summarize'],
                    ['source' => 'summarize', 'target' => 'log_done'],
                ],
            ],
            [
                'name' => 'Фильтр заявок по сумме',
                'user_email' => 'test1@gmail.com',
                'meta' => ['tags' => ['condition', 'sales']],
                'nodes' => [
                    self::webhookNode('trigger', 1, 'Вебхук сделки', 100, 160, 'deal', [
                        ['key' => 'client', 'data_type' => 'string'],
                        ['key' => 'amount', 'data_type' => 'float'],
                    ]),
                    self::conditionNode('check_amount', 2, 'Сумма > 10000', 380, 160, 'deal.amount', '>', 10000, 'vip_mail', 'reject_log'),
                    self::fieldNode('vip_mail', 'email_report', 3, 'VIP уведомление', 660, 60, [
                        'email' => 'sales-vip@example.com',
                    ]),
                    self::fieldNode('reject_log', 'log', 4, 'Обычная сделка', 660, 260, [
                        'message' => 'Сделка ниже порога VIP',
                    ]),
                ],
                'edges' => [
                    ['source' => 'trigger', 'target' => 'check_amount'],
                ],
            ],
            [
                'name' => 'Сбор и обновление метрик',
                'user_email' => 'test1@gmail.com',
                'meta' => ['tags' => ['metrics', 'dashboard']],
                'nodes' => [
                    self::fieldNode('cron', 'schedule', 1, 'Каждый час', 100, 120, [
                        'timing' => '0 * * * *',
                    ]),
                    self::fieldNode('collect', 'collect_metrics', 2, 'Сбор метрик', 380, 120, [
                        'project' => 'main-dashboard',
                    ]),
                    self::fieldNode('update', 'update_metric', 3, 'Обновить виджет', 660, 120, [
                        'updatable_metrics' => [
                            ['widget_id' => 1, 'label' => 'Заявки', 'amount' => 1],
                        ],
                    ]),
                ],
                'edges' => [
                    ['source' => 'cron', 'target' => 'collect'],
                    ['source' => 'collect', 'target' => 'update'],
                ],
            ],
            [
                'name' => 'Mistral-суммаризация текста',
                'user_email' => 'test1@gmail.com',
                'meta' => ['tags' => ['mistral', 'ai']],
                'nodes' => [
                    self::webhookNode('trigger', 1, 'Вебхук текста', 100, 120, 'document', [
                        ['key' => 'title', 'data_type' => 'string'],
                        ['key' => 'body', 'data_type' => 'string'],
                    ]),
                    self::mistralTextNode('summarize', 2, 'Mistral summary', 380, 120, 'Сожми текст до 3 предложений, сохрани смысл.'),
                    self::fieldNode('notify', 'email_report', 3, 'Отправить summary', 660, 120, [
                        'email' => 'editor@example.com',
                    ]),
                ],
                'edges' => [
                    ['source' => 'trigger', 'target' => 'summarize'],
                    ['source' => 'summarize', 'target' => 'notify'],
                ],
            ],
            [
                'name' => 'Анализ скриншота страницы',
                'user_email' => 'test1@gmail.com',
                'meta' => ['tags' => ['mistral', 'vision', 'scraper']],
                'nodes' => [
                    self::fieldNode('cron', 'schedule', 1, 'Раз в сутки', 100, 120, [
                        'timing' => '0 22 * * *',
                    ]),
                    self::fieldNode('screenshot', 'page_loader', 2, 'Загрузка UI', 380, 120, [
                        'url_source' => 'https://example.com/landing',
                        'compression_level' => 'extended',
                    ]),
                    self::mistralPictureNode('analyze', 3, 'Pixtral анализ', 660, 120, 'Опиши визуальные проблемы лендинга и CTA.'),
                    self::fieldNode('log_result', 'log', 4, 'Сохранить отчёт', 940, 120, [
                        'message' => 'Визуальный аудит завершён',
                    ]),
                ],
                'edges' => [
                    ['source' => 'cron', 'target' => 'screenshot'],
                    ['source' => 'screenshot', 'target' => 'analyze'],
                    ['source' => 'analyze', 'target' => 'log_result'],
                ],
            ],
            [
                'name' => 'Heartbeat мониторинг',
                'user_email' => 'test1@gmail.com',
                'meta' => ['tags' => ['monitoring']],
                'nodes' => [
                    self::fieldNode('cron', 'schedule', 1, 'Каждые 5 минут', 100, 100, [
                        'timing' => '*/5 * * * *',
                    ]),
                    self::fieldNode('ping', 'log', 2, 'Пульс сервиса', 380, 100, [
                        'message' => 'Service heartbeat OK',
                    ]),
                ],
                'edges' => [
                    ['source' => 'cron', 'target' => 'ping'],
                ],
            ],
            [
                'name' => 'Голосовая заявка в текст',
                'user_email' => 'test1@gmail.com',
                'meta' => ['tags' => ['whisper', 'ai', 'voice']],
                'nodes' => [
                    self::webhookNode('trigger', 1, 'Аудио вебхук', 100, 120, 'voice', [
                        ['key' => 'audio_file', 'data_type' => 'file'],
                        ['key' => 'caller', 'data_type' => 'string'],
                    ]),
                    self::fieldNode('transcribe', 'go_whisper', 2, 'Whisper ASR', 380, 120, []),
                    self::fieldNode('reply', 'ai_request', 3, 'Ответ по транскрипту', 660, 120, [
                        'prompt' => 'По транскрипту голосового сообщения сформулируй ответ клиенту.',
                    ]),
                ],
                'edges' => [
                    ['source' => 'trigger', 'target' => 'transcribe'],
                    ['source' => 'transcribe', 'target' => 'reply'],
                ],
            ],
            [
                'name' => 'Классификация обращений',
                'user_email' => 'test1@gmail.com',
                'meta' => ['tags' => ['ai', 'routing']],
                'nodes' => [
                    self::webhookNode('trigger', 1, 'Тикет поддержки', 100, 180, 'ticket', [
                        ['key' => 'subject', 'data_type' => 'string'],
                        ['key' => 'body', 'data_type' => 'string'],
                    ]),
                    self::agentNode('classify', 2, 'AI-классификатор', 380, 180, 'Определи категорию и срочность обращения.', 'classification', [
                        ['key' => 'category', 'data_type' => 'string'],
                        ['key' => 'urgent', 'data_type' => 'boolean'],
                    ]),
                    self::conditionNode('route', 3, 'Срочное?', 660, 180, 'classification.urgent', '=', true, 'urgent_mail', 'queue_log'),
                    self::fieldNode('urgent_mail', 'email_report', 4, 'Срочное уведомление', 940, 80, [
                        'email' => 'urgent@example.com',
                    ]),
                    self::fieldNode('queue_log', 'log', 5, 'В очередь', 940, 280, [
                        'message' => 'Тикет в обычной очереди',
                    ]),
                ],
                'edges' => [
                    ['source' => 'trigger', 'target' => 'classify'],
                    ['source' => 'classify', 'target' => 'route'],
                ],
            ],
            [
                'name' => 'Проверка геозоны доставки',
                'user_email' => 'test1@gmail.com',
                'meta' => ['tags' => ['geo', 'logistics']],
                'nodes' => [
                    self::webhookNode('trigger', 1, 'Координаты заказа', 100, 120, 'order', [
                        ['key' => 'order_id', 'data_type' => 'string'],
                        ['key' => 'lat', 'data_type' => 'float'],
                        ['key' => 'lng', 'data_type' => 'float'],
                    ]),
                    self::fieldNode('geo_check', 'point_in_polygon', 2, 'Зона доставки', 380, 120, [
                        'polygon' => [
                            [37.60, 55.75],
                            [37.65, 55.75],
                            [37.65, 55.78],
                            [37.60, 55.78],
                        ],
                    ]),
                    self::fieldNode('notify', 'email_report', 3, 'Результат проверки', 660, 120, [
                        'email' => 'logistics@example.com',
                    ]),
                ],
                'edges' => [
                    ['source' => 'trigger', 'target' => 'geo_check'],
                    ['source' => 'geo_check', 'target' => 'notify'],
                ],
            ],

            // --- test2@gmail.com (5 workflows) ---
            [
                'name' => 'OCR документов по расписанию',
                'user_email' => 'test2@gmail.com',
                'meta' => ['tags' => ['ocr', 'schedule']],
                'nodes' => [
                    self::fieldNode('cron', 'schedule', 1, 'Ночной запуск', 100, 120, [
                        'timing' => '0 2 * * *',
                    ]),
                    self::fieldNode('load_doc', 'page_loader', 2, 'Загрузка PDF-страницы', 380, 120, [
                        'url_source' => 'https://example.com/invoice.pdf',
                        'compression_level' => 'script_style',
                    ]),
                    self::mistralOcrNode('ocr', 3, 'Mistral OCR', 660, 120),
                    self::fieldNode('log_done', 'log', 4, 'OCR завершён', 940, 120, [
                        'message' => 'Документ распознан',
                    ]),
                ],
                'edges' => [
                    ['source' => 'cron', 'target' => 'load_doc'],
                    ['source' => 'load_doc', 'target' => 'ocr'],
                    ['source' => 'ocr', 'target' => 'log_done'],
                ],
            ],
            [
                'name' => 'Извлечение данных из счёта',
                'user_email' => 'test2@gmail.com',
                'meta' => ['tags' => ['ocr', 'ai', 'finance']],
                'nodes' => [
                    self::webhookNode('trigger', 1, 'Загрузка счёта', 100, 120, 'invoice', [
                        ['key' => 'file', 'data_type' => 'file'],
                        ['key' => 'vendor', 'data_type' => 'string'],
                    ]),
                    self::mistralOcrNode('ocr', 2, 'Распознавание', 380, 120),
                    self::agentNode('extract', 3, 'Парсинг полей', 660, 120, 'Извлеки номер, дату, сумму и ИНН из текста счёта.', 'invoice_data', [
                        ['key' => 'number', 'data_type' => 'string'],
                        ['key' => 'date', 'data_type' => 'string'],
                        ['key' => 'total', 'data_type' => 'float'],
                        ['key' => 'inn', 'data_type' => 'string'],
                    ]),
                ],
                'edges' => [
                    ['source' => 'trigger', 'target' => 'ocr'],
                    ['source' => 'ocr', 'target' => 'extract'],
                ],
            ],
            [
                'name' => 'Счётчик обращений в дашборд',
                'user_email' => 'test2@gmail.com',
                'meta' => ['tags' => ['metrics', 'webhook']],
                'nodes' => [
                    self::webhookNode('trigger', 1, 'Новое обращение', 100, 120, 'contact', [
                        ['key' => 'channel', 'data_type' => 'string'],
                        ['key' => 'message', 'data_type' => 'string'],
                    ]),
                    self::fieldNode('ai_tag', 'ai_request', 2, 'Тег темы', 380, 120, [
                        'prompt' => 'Определи одним словом тему обращения.',
                    ]),
                    self::fieldNode('inc_metric', 'update_metric', 3, '+1 к метрике', 660, 120, [
                        'updatable_metrics' => [
                            ['widget_id' => 2, 'label' => 'Обращения', 'amount' => 1],
                        ],
                    ]),
                ],
                'edges' => [
                    ['source' => 'trigger', 'target' => 'ai_tag'],
                    ['source' => 'ai_tag', 'target' => 'inc_metric'],
                ],
            ],
            [
                'name' => 'Еженедельный AI-отчёт',
                'user_email' => 'test2@gmail.com',
                'meta' => ['tags' => ['report', 'mistral']],
                'nodes' => [
                    self::fieldNode('cron', 'schedule', 1, 'Понедельник 8:00', 100, 120, [
                        'timing' => '0 8 * * 1',
                    ]),
                    self::mistralTextNode('report', 2, 'Генерация отчёта', 380, 120, 'Сформируй еженедельный отчёт по KPI: продажи, конверсия, NPS.'),
                    self::fieldNode('send', 'email_report', 3, 'Отправка руководству', 660, 120, [
                        'email' => 'ceo@example.com',
                    ]),
                ],
                'edges' => [
                    ['source' => 'cron', 'target' => 'report'],
                    ['source' => 'report', 'target' => 'send'],
                ],
            ],
            [
                'name' => 'Мониторинг изменений на сайте',
                'user_email' => 'test2@gmail.com',
                'meta' => ['tags' => ['monitoring', 'scraper']],
                'nodes' => [
                    self::fieldNode('cron', 'schedule', 1, 'Каждые 30 минут', 100, 180, [
                        'timing' => '*/30 * * * *',
                    ]),
                    self::fieldNode('fetch', 'page_loader', 2, 'Снимок страницы', 380, 180, [
                        'url_source' => 'https://example.com/pricing',
                        'compression_level' => 'text_only',
                    ]),
                    self::conditionNode('diff_check', 3, 'Есть изменения?', 660, 180, 'page.hash', '!=', 'previous-hash', 'alert_mail', 'noop_log'),
                    self::fieldNode('alert_mail', 'email_report', 4, 'Алерт команде', 940, 80, [
                        'email' => 'devops@example.com',
                    ]),
                    self::fieldNode('noop_log', 'log', 5, 'Без изменений', 940, 280, [
                        'message' => 'Страница не изменилась',
                    ]),
                ],
                'edges' => [
                    ['source' => 'cron', 'target' => 'fetch'],
                    ['source' => 'fetch', 'target' => 'diff_check'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{
     *     workflow_name: string,
     *     user_email: string,
     *     category_title: string,
     *     title: string,
     *     description: string,
     *     downloads: int
     * }>
     */
    public static function catalogEntries(): array
    {
        return [
            [
                'workflow_name' => 'AI-ответ на заявку с сайта',
                'user_email' => 'test1@gmail.com',
                'category_title' => 'AI-агент',
                'title' => 'Автоответ поддержки на заявки',
                'description' => 'Принимает заявку через вебхук, генерирует ответ GigaChat и отправляет на email.',
                'downloads' => 42,
            ],
            [
                'workflow_name' => 'Ежедневный дайджест новостей',
                'user_email' => 'test1@gmail.com',
                'category_title' => 'Web-скраперы',
                'title' => 'Утренний дайджест новостей',
                'description' => 'По cron загружает страницу, суммирует через AI Agent и пишет в лог.',
                'downloads' => 28,
            ],
            [
                'workflow_name' => 'Фильтр заявок по сумме',
                'user_email' => 'test1@gmail.com',
                'category_title' => 'Автоматизация',
                'title' => 'VIP-фильтр сделок',
                'description' => 'Ветвление по сумме сделки: VIP на email, остальные в лог.',
                'downloads' => 15,
            ],
            [
                'workflow_name' => 'Mistral-суммаризация текста',
                'user_email' => 'test1@gmail.com',
                'category_title' => 'AI-агент',
                'title' => 'Краткое summary через Mistral',
                'description' => 'Сжимает длинный текст и отправляет результат редактору.',
                'downloads' => 33,
            ],
            [
                'workflow_name' => 'Классификация обращений',
                'user_email' => 'test1@gmail.com',
                'category_title' => 'Интеграции',
                'title' => 'Маршрутизация тикетов',
                'description' => 'AI определяет срочность и направляет в email или очередь.',
                'downloads' => 51,
            ],
            [
                'workflow_name' => 'Извлечение данных из счёта',
                'user_email' => 'test2@gmail.com',
                'category_title' => 'AI-агент',
                'title' => 'Парсер счетов OCR + Agent',
                'description' => 'Распознаёт счёт Mistral OCR и извлекает структуру JSON.',
                'downloads' => 19,
            ],
            [
                'workflow_name' => 'Еженедельный AI-отчёт',
                'user_email' => 'test2@gmail.com',
                'category_title' => 'Автоматизация',
                'title' => 'Weekly KPI report',
                'description' => 'Каждый понедельник Mistral формирует отчёт и шлёт на email.',
                'downloads' => 12,
            ],
            [
                'workflow_name' => 'Мониторинг изменений на сайте',
                'user_email' => 'test2@gmail.com',
                'category_title' => 'Web-скраперы',
                'title' => 'Watchdog pricing page',
                'description' => 'Периодически снимает страницу и алертит при изменениях.',
                'downloads' => 37,
            ],
        ];
    }

    private static function webhookNode(
        string $key,
        int $order,
        string $title,
        int $x,
        int $y,
        string $groupName,
        array $fields
    ): array {
        return [
            'key' => $key,
            'type' => 'webhook_trigger',
            'order' => $order,
            'title' => $title,
            'config' => [
                'request' => self::outputGroup($groupName, $fields),
            ],
            'position' => ['x' => $x, 'y' => $y],
        ];
    }

    private static function agentNode(
        string $key,
        int $order,
        string $title,
        int $x,
        int $y,
        string $prompt,
        string $groupName,
        array $fields
    ): array {
        return self::fieldNode($key, 'ai_agent_request', $order, $title, $x, $y, [
            'prompt' => $prompt,
            'output' => self::outputGroup($groupName, $fields),
        ]);
    }

    private static function conditionNode(
        string $key,
        int $order,
        string $title,
        int $x,
        int $y,
        string $path,
        string $operator,
        mixed $right,
        string $trueKey,
        string $falseKey
    ): array {
        return [
            'key' => $key,
            'type' => 'condition',
            'order' => $order,
            'title' => $title,
            'config' => [
                'condition' => [
                    'type' => 'comparison',
                    'left' => ['path' => $path],
                    'operator' => $operator,
                    'right' => $right,
                ],
            ],
            'position' => ['x' => $x, 'y' => $y],
            'condition_branches' => ['true' => $trueKey, 'false' => $falseKey],
        ];
    }

    private static function mistralTextNode(string $key, int $order, string $title, int $x, int $y, string $prompt): array
    {
        return self::fieldNode($key, 'mistral_text', $order, $title, $x, $y, [
            'api_key' => 'seed-mistral-key',
            'temperature' => 0.7,
            'max_tokens' => 1024,
            'prompt' => $prompt,
        ]);
    }

    private static function mistralPictureNode(string $key, int $order, string $title, int $x, int $y, string $prompt): array
    {
        return self::fieldNode($key, 'mistral_picture', $order, $title, $x, $y, [
            'api_key' => 'seed-mistral-key',
            'temperature' => 0.3,
            'max_tokens' => 512,
            'prompt' => $prompt,
        ]);
    }

    private static function mistralOcrNode(string $key, int $order, string $title, int $x, int $y): array
    {
        return self::fieldNode($key, 'mistral_ocr', $order, $title, $x, $y, [
            'api_key' => 'seed-mistral-key',
            'temperature' => 0.1,
            'max_tokens' => 2048,
        ]);
    }

    private static function fieldNode(
        string $key,
        string $type,
        int $order,
        string $title,
        int $x,
        int $y,
        array $config
    ): array {
        return [
            'key' => $key,
            'type' => $type,
            'order' => $order,
            'title' => $title,
            'config' => $config,
            'position' => ['x' => $x, 'y' => $y],
        ];
    }

    /**
     * @param  list<array{key: string, data_type: string}>  $fields
     */
    private static function outputGroup(string $name, array $fields): array
    {
        return [
            'type' => 'group',
            'name' => $name,
            'fields' => array_map(fn (array $field) => [
                'type' => 'field',
                'key' => $field['key'],
                'data_type' => $field['data_type'],
                'required' => true,
            ], $fields),
        ];
    }
}
