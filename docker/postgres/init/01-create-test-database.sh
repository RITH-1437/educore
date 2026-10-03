#!/bin/sh
# The PHPUnit suite runs against its own database (backend/phpunit.xml:
# DB_DATABASE=educore_test), never the development one. PostgreSQL runs this
# script once, when the data volume is first initialised; for a volume created
# before it existed, see README §13 ("database educore_test does not exist").
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<EOSQL
CREATE DATABASE educore_test OWNER "$POSTGRES_USER";
EOSQL
