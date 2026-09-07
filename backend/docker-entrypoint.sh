#!/bin/sh
set -e

if [ ! -f config/jwt/private.pem ]; then
    php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
fi

php bin/console doctrine:migrations:migrate --no-interaction

# Railway (and most PaaS providers) inject a dynamic $PORT the app must bind
# to; docker-compose doesn't set it, so this still defaults to 8000 there.
exec php -S "0.0.0.0:${PORT:-8000}" -t public
