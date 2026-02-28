#!/bin/sh
set -e
# Удаляем закэшированный список пакетов, чтобы Laravel пересобрал его из vendor.
# Иначе в томе может остаться старый cache с dev-пакетами (например Pail), которых нет в --no-dev образе.
CACHE_DIR="/var/www/wix/todo/bootstrap/cache"
rm -f "${CACHE_DIR}/packages.php" "${CACHE_DIR}/services.php"
exec "$@"
