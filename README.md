# Laravel Todo

Laravel 12, Inertia, Vue 3, Laravel Reverb (WebSocket), PostgreSQL, Redis, Meilisearch.

---

## Запуск через Docker

**На хосте нужны только Docker, Docker Compose и Nginx.** PHP, Composer и Node не требуются — зависимости и сборка фронта выполняются внутри образа. Заполняете только `.env`.

### Требования

- **Docker** и **Docker Compose**
- **Nginx** на хосте — отдаёт статику из каталога `public` проекта и проксирует PHP и WebSocket в контейнеры

### 1. Клонирование и .env

```bash
git clone <repo> todo && cd todo
cp .env.example .env
```

Заполните `.env` под Docker. Переменные из `.env` используются при сборке образа (для Vite) и при запуске контейнеров.

**Обязательно:**

```env
APP_KEY=base64:...   # см. ниже

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

**Сгенерировать `APP_KEY`** (один раз, через контейнер):

```bash
docker compose run --rm app php artisan key:generate --show
```

Скопируйте вывод в `.env` в переменную `APP_KEY=`.

Ключи Reverb можно задать вручную или сгенерировать: `docker compose run --rm app php artisan reverb:install` (если нужен вывод в консоль).

**Опционально — поиск (Meilisearch):**

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey
```

### 2. Сборка образа

Composer, npm и сборка фронта выполняются внутри Docker:

```bash
docker compose build app
```

При сборке в образ подставляются переменные из вашего `.env` (в т.ч. `VITE_REVERB_*` для фронта). Убедитесь, что `.env` заполнен до сборки.

### 3. Копирование public на хост (для Nginx)

Nginx на хосте отдаёт статику из каталога `public` проекта. Скопируйте содержимое `public` из образа в каталог проекта на хосте (один раз после первой сборки и после каждого пересборки образа):

```bash
docker compose run --rm -v "$(pwd)/public:/host/public" app sh -c "cp -r /var/www/wix/todo/public/. /host/public/"
```

В результате в `./public` появятся `build/`, `index.php` и остальные файлы из образа.

### 4. Запуск контейнеров

```bash
docker compose up -d
```

Сервисы:

| Сервис      | Порт на хосте | Назначение              |
|-------------|----------------|-------------------------|
| app         | 9000           | PHP-FPM                 |
| reverb      | 8081           | WebSocket (чат)         |
| queue       | —              | Очередь задач (Redis)   |
| db          | 5433           | PostgreSQL              |
| redis       | 6380           | Redis                   |
| meilisearch | 7701           | Поиск                   |

Миграции (при первом запуске):

```bash
docker compose exec app php artisan migrate --force
```

При необходимости:

```bash
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan scout:import "App\Models\Track"
```

### 5. Nginx на хосте

- **root** — путь к каталогу **public** проекта на хосте (например `/home/user/projects/todo/public`).
- PHP — `fastcgi_pass 127.0.0.1:9000`.
- WebSocket — `location /app` → `proxy_pass http://127.0.0.1:8081`.

Пример конфига:

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

Перезагрузка Nginx: `sudo nginx -t && sudo systemctl reload nginx`.

Если `root` указывает на симлинк в домашний каталог, может понадобиться: `sudo chmod o+x /home/username`.

### 6. Проверка

- Сайт: `http://localhost` (или ваш `server_name`).
- Логи: `docker compose logs -f reverb` (и при необходимости `app`, `queue`).

---

## Полезные команды

Пересборка образа (после изменений кода, `_Docker/Dockerfile` или `_Docker/php.ini`):

```bash
docker compose build app
docker compose up -d app reverb queue
```

После пересборки снова скопируйте public на хост (шаг 3).

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

Остановка:

```bash
docker compose down
```

Данные приложения (логи, кэш, загрузки) хранятся в томах Docker (`wix-storage`, `wix-cache`). При `docker compose down -v` тома удаляются.

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
