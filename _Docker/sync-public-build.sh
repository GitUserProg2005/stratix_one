#!/bin/sh
# Копирует Vite build из запущенного контейнера app на хост (для nginx).
# Запуск на VDS из корня репозитория: sh _Docker/sync-public-build.sh

set -e

############################
# Пути — правь под свой VDS
############################
CONTAINER_NAME="stratix_one-app"
CONTAINER_BUILD_PATH="/var/www/stratix_one/public/build"
HOST_BUILD_PATH="/var/www/stratix_one/public/build"

############################
# 1. Контейнер должен быть запущен
############################
if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER_NAME"; then
    echo "Ошибка: контейнер «${CONTAINER_NAME}» не запущен." >&2
    echo "Сначала: docker compose up -d app" >&2
    exit 1
fi

############################
# 2. В образе есть собранный фронт
############################
if ! docker exec "$CONTAINER_NAME" sh -c "test -f '${CONTAINER_BUILD_PATH}/manifest.json' || test -f '${CONTAINER_BUILD_PATH}/.vite/manifest.json'"; then
    echo "Ошибка: в контейнере нет Vite manifest в ${CONTAINER_BUILD_PATH}" >&2
    echo "Пересобери образ: docker compose build app && docker compose up -d app" >&2
    exit 1
fi

############################
# 3. Атомарно: staging → replace
############################
STAGING_DIR="$(mktemp -d "${HOST_BUILD_PATH}.staging.XXXXXX")"
trap 'rm -rf "$STAGING_DIR"' EXIT INT TERM

mkdir -p "$(dirname "$HOST_BUILD_PATH")"

echo "Копирую ${CONTAINER_NAME}:${CONTAINER_BUILD_PATH} → ${STAGING_DIR}"
docker cp "${CONTAINER_NAME}:${CONTAINER_BUILD_PATH}/." "${STAGING_DIR}/"

if command -v rsync >/dev/null 2>&1; then
    mkdir -p "$HOST_BUILD_PATH"
    rsync -a --delete "${STAGING_DIR}/" "${HOST_BUILD_PATH}/"
else
    rm -rf "${HOST_BUILD_PATH}.old"
    if [ -d "$HOST_BUILD_PATH" ]; then
        mv "$HOST_BUILD_PATH" "${HOST_BUILD_PATH}.old"
    fi
    mv "$STAGING_DIR" "$HOST_BUILD_PATH"
    rm -rf "${HOST_BUILD_PATH}.old"
    trap - EXIT INT TERM
fi

# nginx обычно читает от www-data
if id www-data >/dev/null 2>&1; then
    chown -R www-data:www-data "$HOST_BUILD_PATH" 2>/dev/null || true
fi

echo "Готово: ${HOST_BUILD_PATH}"
