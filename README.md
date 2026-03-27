# Laravel Todo

Laravel 12, Inertia, Vue 3, Laravel Reverb (WebSocket), PostgreSQL, Redis, Meilisearch. Админка MoonShine, очереди (Redis), загрузка файлов в S3.

---

## Запуск через Docker

На хосте нужны только **Docker**, **Docker Compose** и **Nginx**. PHP, Composer и Node на хосте не требуются — всё выполняется в образе. Вы заполняете только `.env` в каталоге проекта на хосте; файла `.env` внутри контейнера нет — переменные передаются через `env_file` при запуске.

### Два варианта `docker compose`

| Файл | Назначение |
|------|------------|
| **`docker-compose.yml`** | Разработка на VDS: **bind-mount** кода, тома `wix-vendor` и `wix-node-modules`. Контейнер `app` при старте выполняет `composer install` и при отсутствии `public/build` — `npm ci && npm run build`. Порт **5173** для Vite. Копировать `public` из образа на хост не нужно. |
| **`docker-compose.prod.yml`** | Продакшен / CI: только образ, без монтирования кода. Деплой: `docker compose -f docker-compose.prod.yml up -d`. Статику на хост для Nginx — см. раздел 7. |

### Требования

- Docker и Docker Compose
- Nginx на хосте (статика + прокси на PHP-FPM и Reverb)

---

### 1. Клонирование и каталог проекта

```bash
git clone <repo> rus_spotify && cd rus_spotify
cp .env.example .env
```

Все команды `docker compose` выполняйте **из этого каталога** — по нему Compose ищет `.env`.

---

### 2. Заполнение .env (порядок)

Редактируйте **только** файл `.env` на хосте (рядом с `docker-compose.yml`).

**Шаг 2.1 — база (PostgreSQL cluster: primary + replica + Pgpool) и Redis:**

Ниже два готовых варианта `.env` для БД-кластера.  
Laravel уже настроен на read/write split:
- **write** -> `DB_HOST:DB_PORT` (primary через Pgpool/или напрямую в docker-сети)
- **read** -> `DB_REPLICA_HOST:DB_REPLICA_PORT` (replica)

**Вариант A: Laravel локально, БД в Docker (хостовые порты)**

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_REPLICA_HOST=127.0.0.1
DB_REPLICA_PORT=5433
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=secret
```

**Вариант B: full Docker (Laravel внутри compose, внутренние хосты/порты)**

```env
DB_CONNECTION=pgsql
DB_HOST=pgpool
DB_PORT=5432
DB_REPLICA_HOST=postgres-replica
DB_REPLICA_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=secret

REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis
```

**Шаг 2.2 — ключ приложения (обязательно):**

Сгенерируйте ключ и вставьте в `.env`:

```bash
docker compose run --rm app php artisan key:generate --show
```

В выводе будет строка вида `base64:...`. В `.env` добавьте или замените:

```env
APP_KEY=base64:скопируйте_сюда_вывод_команды
```

**Шаг 2.3 — Reverb (чат, WebSocket):**

```env
BROADCAST_DRIVER=reverb

REVERB_APP_ID=885140
REVERB_APP_KEY=ваш_ключ
REVERB_APP_SECRET=ваш_секрет
REVERB_HOST=localhost
REVERB_PORT=8081
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Ключи Reverb можно задать вручную (любые строки) или сгенерировать: `docker compose run --rm app php artisan reverb:install`.

**Шаг 2.4 — S3 (для загрузки треков/превью в админке):**

Если используете S3 (или совместимое хранилище), заполните **до сборки образа** и перед запуском:

```env
AWS_ACCESS_KEY_ID=ваш_ключ
AWS_SECRET_ACCESS_KEY=ваш_секрет
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=имя_бакета
```

Для S3-совместимого сервиса (MinIO, DigitalOcean Spaces и т.п.) добавьте при необходимости:

```env
AWS_ENDPOINT=https://...
AWS_USE_PATH_STYLE_ENDPOINT=true
```

**Шаг 2.5 — YooKassa (оплата подписки / тарифы):**

Для работы страницы тарифов (`/rates`) и редиректа на оплату нужны данные из [личного кабинета YooKassa](https://yookassa.ru/):

```env
SHOP_ID=номер_магазина
SK_YK=секретный_ключ
```

- **SHOP_ID** — идентификатор магазина (число).
- **SK_YK** — секретный ключ (выдаётся в настройках магазина, формат вида `live_...` или `test_...`).

Без этих переменных создание платежа не сработает, пользователь увидит ошибку на странице тарифов.

**Шаг 2.6 — опционально (поиск Meilisearch):**

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey
```

Проверка, что переменные попадают в контейнер (после первого `up`):

```bash
docker compose exec app printenv APP_KEY
docker compose exec app printenv AWS_BUCKET
```

Пустой вывод — значит в `.env` в каталоге запуска нет этой переменной или она пустая.

---

### 3. Сборка образа

Composer, npm и сборка фронта для **образа** выполняются при `docker build`. Переменные `VITE_*` подставляются из `.env` при сборке.

Разработка (`docker-compose.yml`):

```bash
docker compose build app
```

Продакшен:

```bash
docker compose -f docker-compose.prod.yml build app
```

---

### 4. Запуск контейнеров

Разработка (bind-mount):

```bash
docker compose up -d
```

Продакшен:

```bash
docker compose -f docker-compose.prod.yml up -d
```

| Сервис            | Порт                | Назначение                      |
|-------------------|---------------------|----------------------------------|
| app               | 9000                | PHP-FPM                          |
| reverb            | 8081                | WebSocket (чат)                  |
| queue             | —                   | Очередь (Redis)                  |
| pgpool            | 5432 (host -> cont) | Точка входа в кластер PostgreSQL |
| postgres-primary  | internal 5432       | Primary PostgreSQL               |
| postgres-replica  | 5433 (host), 5432   | Replica PostgreSQL               |
| redis             | 6379                | Redis                            |
| meilisearch       | 7700                | Поиск                            |

---

### 5. Миграции (обязательно перед использованием)

Без миграций будут ошибки вида «relation "sessions" does not exist» / «relation "cache" does not exist». Выполните один раз:

```bash
docker compose exec app php artisan migrate --force
```

При необходимости:

```bash
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan scout:import "App\Models\Track"
```

---

### 6. Права на storage и bootstrap/cache

Логи, кэш и загрузки пишутся в тома `wix-storage` и `wix-cache`. PHP-FPM работает от пользователя `www-data` — каталоги должны быть ему доступны на запись. Если появляется «Permission denied» при записи в `storage/logs` или при сохранении файлов, выполните один раз:

```bash
docker compose exec app chown -R www-data:www-data /var/www/wix/todo/storage /var/www/wix/todo/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/wix/todo/storage /var/www/wix/todo/bootstrap/cache
```

---

### 7. Статика `public` для Nginx

Статику (CSS, JS, `build/`) отдаёт Nginx с хоста из каталога `public` проекта.

- При **`docker-compose.yml`** (разработка): `public/build` появляется в каталоге проекта на хосте — **копировать из контейнера не нужно**. После правок фронта: `docker compose exec app npm run build` или `FORCE_FRONTEND_BUILD=1 docker compose up -d app`.

- При **`docker-compose.prod.yml`**:

```bash
docker compose -f docker-compose.prod.yml run --rm -v "$(pwd)/public:/host/public" app sh -c "cp -r /var/www/wix/todo/public/. /host/public/"
```

**Vite dev (HMR):** `docker compose exec app npm run dev -- --host 0.0.0.0 --port 5173`, порт 5173 проброшен в dev-compose; при HTTPS настройте прокси в Nginx.

---

### 8. Nginx на хосте

- **root** — путь к каталогу **public** проекта **на хосте** (например `/var/www/rus_spotify/public`). По этому пути Nginx отдаёт статику.
- PHP обрабатывается в контейнере; путь к скрипту **внутри контейнера** всегда `/var/www/wix/todo/public/index.php` — его нужно явно передать в FastCGI.

**Важно:** используйте в `location ~ \.php$` именно `fastcgi_param SCRIPT_FILENAME /var/www/wix/todo/public/index.php;` (путь в контейнере), а не `$document_root$fastcgi_script_name` (иначе будет 404).

Пример конфига:

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    server_name localhost;
    root /var/www/rus_spotify/public;   # путь к public на хосте
    index index.php;
    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME /var/www/wix/todo/public/index.php;
        include fastcgi_params;
    }

    location /app {
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_pass http://127.0.0.1:8081;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Перезагрузка Nginx:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

Если `root` указывает на каталог по симлинку из домашней папки, Nginx может не видеть файлы (404 на статику). Тогда на хосте выполните: `sudo chmod o+x /home/ваш_пользователь`.

---

### 9. Проверка

- Сайт: `http://localhost` (или ваш `server_name`).
- Логи: `docker compose logs -f app`, `docker compose logs -f reverb`, `docker compose logs -f queue`.

---

### 10. Бэкапы (S3) и хостовый cron

Бэкапы делаются пакетом [spatie/laravel-backup](https://github.com/spatie/laravel-backup): дамп БД и файлы приложения упаковываются и отправляются на диск `s3` (настройки в `config/backup.php`). Запуск по расписанию вынесен на **локальный cron** на хосте — без отдельного контейнера.

**Требования:**

- В `.env` заполнены переменные S3 (см. шаг 2.4): `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`.
- На сервере установлен и запущен **cron** (обычно уже есть: `systemctl status cron`).

**Ручной запуск (проверка):**

```bash
cd /var/www/rus_spotify
docker compose -f docker-compose.prod.yml exec app php /var/www/wix/todo/artisan backup:run
```

В режиме разработки (`docker-compose.yml`) можно вызывать `docker compose exec app …` без `-f`.

Успешный запуск создаёт архив на S3. Ошибки выводятся в консоль.

**Настройка cron на хосте:**

1. Откройте crontab пользователя, под которым крутится проект (или root):
   ```bash
   crontab -e
   ```
2. Добавьте строку. Пример — раз в день в 03:00, с записью лога и статуса:
   ```cron
   0 3 * * * cd /var/www/rus_spotify && docker compose -f docker-compose.prod.yml exec -T app php /var/www/wix/todo/artisan backup:run >> /var/www/rus_spotify/backup.log 2>&1; echo "$(date -Iseconds) exit=$?" >> /var/www/rus_spotify/backup-status.log
   ```
   Путь `/var/www/rus_spotify` замените на фактический каталог проекта на хосте. Флаг `-T` нужен для запуска из cron (без TTY).

3. Сохраните и выйдите. Задача будет выполняться по расписанию.

Другие варианты расписания и отслеживания успеха/провала — в файле **`_Docker/cron-host.example`** (письма от Laravel, MAILTO, только лог без статуса и т.д.).

**Отслеживание результата:**

- **backup.log** — полный вывод команды (ошибки Laravel/S3 видны здесь).
- **backup-status.log** — после каждого запуска строка вида `2025-03-04T03:00:00+00:00 exit=0`; `exit=0` — успех, иначе ошибка. Просмотр: `tail -5 /var/www/rus_spotify/backup-status.log`.
- **Почта:** в `config/backup.php` включены уведомления при успехе и при ошибке. Настройте в `.env` отправку почты и укажите свой адрес в `config/backup.php` → `notifications.mail.to`, чтобы получать письма о результате бэкапа.

---

## Краткий порядок запуска (чек-лист)

1. Клонировать репозиторий, `cp .env.example .env`.
2. Заполнить `.env`: DB_*, REDIS_*, QUEUE_CONNECTION, BROADCAST_DRIVER, REVERB_*, VITE_REVERB_*, при необходимости AWS_*, SCOUT_*.
3. Сгенерировать и прописать `APP_KEY`: `docker compose run --rm app php artisan key:generate --show`.
4. Собрать образ: `docker compose build app` (прод: `docker compose -f docker-compose.prod.yml build app`).
5. Запустить контейнеры: `docker compose up -d` (прод: `-f docker-compose.prod.yml`).
6. Выполнить миграции: `docker compose exec app php artisan migrate --force`.
7. При необходимости поправить права: `docker compose exec app chown -R www-data:www-data /var/www/wix/todo/storage /var/www/wix/todo/bootstrap/cache`.
8. Для **`docker-compose.prod.yml`** — скопировать `public` на хост (раздел 7). Для **`docker-compose.yml`** шаг не нужен.
9. Настроить Nginx (root на public хоста, SCRIPT_FILENAME на путь в контейнере, proxy на 8081 для `/app`).
10. (Опционально) Настроить бэкапы в S3 и хостовый cron — см. раздел «Бэкапы (S3) и хостовый cron»; примеры в `_Docker/cron-host.example`.

---

## Полезные команды

Пересборка образа (если меняли Dockerfile) и перезапуск:

```bash
docker compose build app
docker compose up -d app reverb queue
```

С bind-mount правки PHP/Vue без пересборки образа; фронт: `docker compose exec app npm run build`. Для **`docker-compose.prod.yml`** после пересборки образа снова скопируйте `public` (раздел 7).

Логи:

```bash
docker compose logs -f app
docker compose logs -f reverb
docker compose logs -f queue
```

Миграции:

```bash
docker compose exec app php artisan migrate --force
```

Ручной запуск бэкапа (в S3):

```bash
docker compose exec app php /var/www/wix/todo/artisan backup:run
```

На проде с **`docker-compose.prod.yml`**: `docker compose -f docker-compose.prod.yml exec …`.

Остановка:

```bash
docker compose down
```

Данные приложения хранятся в томах `wix-storage` и `wix-cache`; в режиме разработки также `wix-vendor` и `wix-node-modules`. При `docker compose down -v` тома удаляются.

---

## Что есть в образе

- PHP 8.4-FPM, расширения: pdo_pgsql, pgsql, mbstring, zip, opcache, pcntl, redis.
- Composer-зависимости (`composer install --no-dev`).
- Сборка фронта (npm ci, npm run build) и `public/build` в образе.
- Кастомный php.ini: `upload_max_filesize` / `post_max_size` 50M, `memory_limit` 256M, `max_execution_time` 120.
- ffmpeg (для конвертации в HLS и создания сниппетов).
- Entrypoint при старте удаляет закэшированные `packages.php` и `services.php` из bootstrap/cache, чтобы не подтягивались dev-пакеты (например Pail), которых нет в образе.
- В финальный образ добавлены **Node/npm** для режима `DEV_MOUNT=1` (composer/npm в контейнере без Node на хосте).

---

## Локальная разработка без Docker

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run dev
php artisan migrate
php artisan reverb:start
```

---

## License

[MIT](https://opensource.org/licenses/MIT).
