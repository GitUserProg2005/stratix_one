# Drivee Gamification Platform

Laravel 12 + Inertia + Vue 3 приложение для геймификации кампании Drivee.

## Стек

- Laravel 12
- Inertia.js + Vue 3
- PostgreSQL (dev/prod конфигурации через Docker Compose)
- Redis
- Meilisearch
- Reverb (WebSocket)
- Геосервисы: OSRM, TileServer GL, Nominatim

## Быстрый старт (Docker)

1. Скопировать переменные окружения:

```bash
cp .env.example .env
```

2. Собрать и запустить сервисы:

```bash
docker compose up -d --build
```

3. Выполнить миграции:

```bash
docker compose exec app php artisan migrate --force
```

4. (Опционально) заполнить тестовыми данными:

```bash
docker compose exec app php artisan db:seed --force
```

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
