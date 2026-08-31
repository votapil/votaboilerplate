#!/bin/sh
# Runs once, when postgres initialises an empty data directory.
#
# The suite runs against its own database (phpunit.xml forces
# DB_DATABASE=<POSTGRES_DB>_testing) so that RefreshDatabase can never truncate
# the database you develop against. Nothing creates it for you, and Pest simply
# refuses to start without it — so create it here, at the only moment we are
# guaranteed to be the only client.
#
# Already have a volume from before this file existed? Run `make test-db`.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
	CREATE DATABASE "${POSTGRES_DB}_testing" OWNER "$POSTGRES_USER";
EOSQL

echo "created database ${POSTGRES_DB}_testing"
