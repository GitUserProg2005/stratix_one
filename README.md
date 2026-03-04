# Laravel Todo

Laravel 12, Inertia, Vue 3, Laravel Reverb (WebSocket), PostgreSQL, Redis, Meilisearch. Админка MoonShine, очереди (Redis), загрузка файлов в S3.

---

## Запуск через Docker

На хосте нужны только **Docker**, **Docker Compose** и **Nginx**. PHP, Composer и Node не требуются — всё выполняется в образе. Вы заполняете только `.env` в каталоге проекта на хосте; файла `.env` внутри контейнера нет — переменные передаются через `env_file` при запуске.

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

**Шаг 2.1 — база и Redis (обязательно):**

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=wix
DB_USERNAME=wix_user
DB_PASSWORD=wix@228339

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

**Шаг 2.5 — опционально (поиск Meilisearch):**

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

Composer, npm и сборка фронта выполняются внутри Docker. Переменные `VITE_REVERB_*` и `APP_NAME` подставляются из `.env` при сборке.

```bash
docker compose build app
```

---

### 4. Запуск контейнеров

```bash
docker compose up -d
```

| Сервис      | Порт   | Назначение        |
|-------------|--------|--------------------|
| app         | 9000   | PHP-FPM            |
| reverb      | 8081   | WebSocket (чат)    |
| queue       | —      | Очередь (Redis)    |
| db          | 5433   | PostgreSQL         |
| redis       | 6380   | Redis              |
| meilisearch | 7701   | Поиск              |

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

### 7. Копирование public на хост (для Nginx)

Статику (CSS, JS, `build/`) отдаёт Nginx с хоста из каталога `public` проекта. Скопируйте содержимое `public` из образа в каталог проекта на хосте (один раз после первой сборки и после каждого пересборки образа):

```bash
docker compose run --rm -v "$(pwd)/public:/host/public" app sh -c "cp -r /var/www/wix/todo/public/. /host/public/"
```

В `./public` появятся `build/`, `index.php` и остальные файлы.

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
docker compose exec app php /var/www/wix/todo/artisan backup:run
```

Успешный запуск создаёт архив на S3. Ошибки выводятся в консоль.

**Настройка cron на хосте:**

1. Откройте crontab пользователя, под которым крутится проект (или root):
   ```bash
   crontab -e
   ```
2. Добавьте строку. Пример — раз в день в 03:00, с записью лога и статуса:
   ```cron
   0 3 * * * cd /var/www/rus_spotify && docker compose exec -T app php /var/www/wix/todo/artisan backup:run >> /var/www/rus_spotify/backup.log 2>&1; echo "$(date -Iseconds) exit=$?" >> /var/www/rus_spotify/backup-status.log
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
4. Собрать образ: `docker compose build app`.
5. Запустить контейнеры: `docker compose up -d`.
6. Выполнить миграции: `docker compose exec app php artisan migrate --force`.
7. При необходимости поправить права: `docker compose exec app chown -R www-data:www-data /var/www/wix/todo/storage /var/www/wix/todo/bootstrap/cache`.
8. Скопировать public на хост: `docker compose run --rm -v "$(pwd)/public:/host/public" app sh -c "cp -r /var/www/wix/todo/public/. /host/public/"`.
9. Настроить Nginx (root на public хоста, SCRIPT_FILENAME на путь в контейнере, proxy на 8081 для `/app`).
10. (Опционально) Настроить бэкапы в S3 и хостовый cron — см. раздел «Бэкапы (S3) и хостовый cron»; примеры в `_Docker/cron-host.example`.

---

## Полезные команды

Пересборка образа и перезапуск:

```bash
docker compose build app
docker compose up -d app reverb queue
```

После пересборки снова скопировать public на хост (шаг 7).

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

Остановка:

```bash
docker compose down
```

Данные приложения хранятся в томах `wix-storage` и `wix-cache`. При `docker compose down -v` тома удаляются.

---

## Что есть в образе

- PHP 8.4-FPM, расширения: pdo_pgsql, pgsql, mbstring, zip, opcache, pcntl, redis.
- Composer-зависимости (`composer install --no-dev`).
- Сборка фронта (npm ci, npm run build) и `public/build` в образе.
- Кастомный php.ini: `upload_max_filesize` / `post_max_size` 50M, `memory_limit` 256M, `max_execution_time` 120.
- ffmpeg (для конвертации в HLS и создания сниппетов).
- Entrypoint при старте удаляет закэшированные `packages.php` и `services.php` из bootstrap/cache, чтобы не подтягивались dev-пакеты (например Pail), которых нет в образе.

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
