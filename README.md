# Laravel Todo

Laravel 12, Inertia, Vue 3, Laravel Reverb (WebSocket), PostgreSQL, Redis, Meilisearch.

---

## Запуск через Docker

Приложение работает в контейнерах; веб-сервер (Nginx) и сборка фронта — на хосте.

### Требования

- **Docker** и **Docker Compose**
- **Node.js** и **npm** — для сборки фронта (`npm run build`)
- **Nginx** на хосте — отдаёт статику и проксирует PHP и WebSocket в контейнеры (или другой способ доступа к портам 9000 и 8081)

### 1. Клонирование и .env

```bash
git clone <repo> todo && cd todo
cp .env.example .env
```

Отредактируйте `.env` под Docker.

**База и Redis (обязательно):**

```env
APP_KEY=base64:...   # сгенерировать: php artisan key:generate

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
```

**Reverb (чат, WebSocket):**

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

`REVERB_HOST=localhost` и порт 8081 в `.env` нужны для **браузера** (подключение к Reverb с хоста). Для контейнера **app** в `docker-compose.yml` уже заданы `REVERB_HOST=reverb` и `REVERB_PORT=8081` — менять их в `.env` не нужно.

Ключи Reverb можно сгенерировать локально: `php artisan reverb:install` (или задать вручную любые значения).

**Опционально — поиск (Meilisearch):**

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey
```

Сгенерировать ключ приложения (один раз, на хосте, в каталоге проекта):

```bash
php artisan key:generate
```

### 2. Сборка фронта

В образе нет Node; фронт собирается на хосте. В корне проекта:

```bash
npm ci
npm run build
```

Появится каталог `public/build`. Он монтируется в контейнеры, статику отдаёт Nginx с хоста. Без билда будут 404 на ассеты и возможные ошибки манифеста Vite.

Если `public/build` создался от root и `npm run build` падает с EACCES:

```bash
sudo chown -R "$USER:$USER" public/build
```

### 3. Запуск контейнеров

Сборка образа приложения (при первом запуске или после изменений Dockerfile):

```bash
docker compose build app
```

Запуск всех сервисов:

```bash
docker compose up -d
```

Сервисы:

| Сервис   | Порт на хосте | Назначение              |
|----------|----------------|-------------------------|
| app      | 9000           | PHP-FPM (обработка PHP) |
| reverb   | 8081           | WebSocket (чат)         |
| db       | 5433           | PostgreSQL              |
| redis    | 6380           | Redis                   |
| meilisearch | 7701        | Поиск                   |

Миграции (при первом запуске):

```bash
docker compose exec app php artisan migrate --force
```

При необходимости — сиды и индексы поиска:

```bash
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan scout:import "App\Models\Track"
```

### 4. Nginx на хосте

Nginx должен:

- отдавать статику и `index.php` из каталога **public** проекта;
- проксировать PHP на `127.0.0.1:9000` (контейнер **app**);
- проксировать WebSocket (путь `/app`) на `127.0.0.1:8081` (контейнер **reverb**).

**Важно:** `root` в Nginx должен указывать на **реальный путь к проекту на хосте** (например `/home/user/projects/todo/public`). Если используете симлинк из `/var/www/...` в домашний каталог, Nginx должен иметь право прохода по каталогам до проекта (например: `sudo chmod o+x /home/username`).

Пример конфига сайта:

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    server_name localhost;
    root /путь/к/проекту/todo/public;
    index index.php;

    location ^~ /build/ {
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # WebSocket (Laravel Reverb)
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

Замените `/путь/к/проекту/todo/public` на свой путь. Перезагрузка Nginx:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### 5. Проверка

- Сайт: `http://localhost` (или ваш `server_name`).
- Reverb: в логах не должно быть падений. Проверка: `docker compose logs -f reverb`.

---

## Полезные команды

Пересборка образа и перезапуск app/reverb после изменений кода или Dockerfile:

```bash
docker compose build app
docker compose up -d app reverb
```

После изменений фронта на хосте:

```bash
npm run build
```

Логи:

```bash
docker compose logs -f app
docker compose logs -f reverb
```

Миграции:

```bash
docker compose exec app php artisan migrate --force
```

Остановка:

```bash
docker compose down
```

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

В `.env`: `BROADCAST_DRIVER=reverb`, переменные `REVERB_*` и при необходимости `DB_*`, `REDIS_*` для локальных сервисов.

---

## License

[MIT](https://opensource.org/licenses/MIT).
