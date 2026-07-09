# stratix_one

[Status](./)
[Laravel](https://laravel.com/)
[Inertia](https://inertiajs.com/)
[Vue](https://vuejs.org/)
[Docker](https://docs.docker.com/compose/)

No-code **n8n-графовый движок**. Позволяет собирать workflow из нод и связей. Между нодами есть **трансформация данных через AST-конструктор**.

## Стек

- Laravel 12
- Inertia.js + Vue 3
- PostgreSQL
- Redis
- Meilisearch
- Reverb (WebSocket)
- Геосервисы: OSRM, TileServer GL, Nominatim
- Docker / Docker Compose
- AI: GigaChat, Mistral (интеграции)

## Деплой (Docker Compose)

### Копируем .env

```bash
cp .env.example .env
```

### Билдим app

```bash
docker compose build app
docker compose up -d
```

### Миграции

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

### Доступ к логам для www-data

```bash
docker compose exec app chown -R www-data:www-data /var/www/stratix_one/storage /var/www/stratix_one/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/stratix_one/storage /var/www/stratix_one/bootstrap/cache
```

### Копирование статики на локальный хост для nginx

```bash
sh _Docker/sync-public-build.sh
```

## Фичи

- **Ноды без “каши аккаунтов”**: часть логики делается нативно внутри проекта.
- **Realtime исполнение**: отслеживание шагов workflow в реальном времени (Reverb).
- **Мультиворкинг**: несколько людей могут работать над одним workflow + lock от гонок.
- **Дашборды/метрики**: можно обновлять метрики через ноды.
- **Каталог workflow**: готовые решения (jsonb структура графа) + копирование.
- **Тарифная сетка**: ограничения по типам нод.
- **Трансформация данных**: AST-маппинг input → output.
- **AI и медиа**: GigaChat, Mistral (text/picture/ocr), Whisper ASR.
- **Гео-инструменты**: OSRM, TileServer GL, Nominatim, Point-in-Polygon (PostGIS).

## Типы нод (N8N)

Список соответствует `NodeRegistry.php`, `NodeTypeSeeder.php` и `nodeConfigFields.js`.

- `**webhook_trigger`**: триггер входящего вебхука.
- `**condition**`: условное ветвление.
- `**ai_request**`: запрос к GigaChat.
- `**ai_agent_request**`: GigaChat с JSON-режимом (структурированный ответ).
- `**email_report**`: отправка результата на email.
- `**osrm**`: построение маршрутов/оптимизаций (route / trip / multi).
- `**log**`: запись в лог приложения.
- `**collect_metrics**`: сбор метрик (заглушка).
- `**update_metric**`: обновление метрик/виджетов.
- `**schedule**`: расписание (cron).
- `**page_loader**`: загрузка страницы и конвертация в markdown.
- `**go_whisper**`: распознавание речи (Whisper ASR).
- `**mistral_text**`: текстовый запрос к Mistral.
- `**mistral_picture**`: анализ изображения (Pixtral).
- `**mistral_ocr**`: OCR документов.
- `**point_in_polygon**`: проверка “точка в полигоне” (PostGIS).

## Геосервисы: OSRM, TileServer, Nominatim

В проекте используются отдельные контейнеры для маршрутизации, тайлов и геокодирования.

### 1) OSRM (маршруты)

Подготовка данных:

- положите `map.osm.pbf` в `_Docker/osrm/`
- выполните препроцессинг:

```bash
bash _Docker/osrm/preprocess.sh
```

Запуск OSRM:

```bash
docker compose up -d osrm
```

Проверка:

- `http://localhost:5000/route/v1/driving/37.6173,55.7558;37.6208,55.7539?overview=false`

### 2) TileServer GL (карта/тайлы)

Убедитесь, что `.mbtiles` файл лежит в `_Docker/tiles/` (например `osm-2020-02-10-v3.11_russia_moscow.mbtiles`).

Запуск:

```bash
docker compose up -d tileserver
```

Проверка:

- `http://localhost:8082`

### 3) Nominatim (геокодинг)

Запуск:

```bash
docker compose up -d nominatim
```

Проверка:

- `http://localhost:8083/search?q=Moscow&format=json`

Примечание: при первом запуске Nominatim импортирует данные из `NOMINATIM_PBF_URL` (см. `docker-compose.yml`), это может занять заметное время.

## Полезные команды

Просмотр логов:

```bash
docker compose logs -f app
docker compose logs -f osrm
docker compose logs -f tileserver
docker compose logs -f nominatim
```

Остановка:

```bash
docker compose down
```

Остановка с удалением томов:

```bash
docker compose down -v
```

