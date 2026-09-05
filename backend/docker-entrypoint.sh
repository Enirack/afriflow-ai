#!/bin/sh
set -e

if [ ! -f config/jwt/private.pem ]; then
    php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
fi

php bin/console doctrine:migrations:migrate --no-interaction

exec "$@"
