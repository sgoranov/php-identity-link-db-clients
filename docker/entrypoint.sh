#!/usr/bin/env bash

cd /var/www/

# Run composer install
composer install --no-scripts

# Database setup
until psql -c "\q"; do sleep 3; done
echo "SELECT 'CREATE DATABASE \"php-identity-link-db-clients\"' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '\"php-identity-link-db-clients\"')\gexec" \
 | psql -v ON_ERROR_STOP=1
php bin/console -e dev doctrine:migrations:migrate --no-interaction

# PHPUnit setup
echo "SELECT 'CREATE DATABASE \"php-identity-link-db-clients-test\"' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '\"php-identity-link-db-clients-test\"')\gexec" \
 | psql -v ON_ERROR_STOP=1
php bin/console -e test doctrine:migrations:migrate --no-interaction
php bin/console -e test -n doctrine:fixtures:load

# Set correct permissions on var/
rm -rf var/cache/*
chmod -R o+rw var/

# This will exec the CMD from Dockerfile
exec "$@"
