#!/bin/bash

# This script is the entrypoint for the Docker container.
# It waits for the database to be ready and then runs migrations.

# Exit immediately if a command exits with a non-zero status.
set -e

# --- Wait for the Database to be Ready ---
# We will try to connect to the database in a loop until it succeeds.
echo "Waiting for database to be ready..."
until mysql -h"db" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "SELECT 1"; do
  >&2 echo "Database is unavailable - sleeping"
  sleep 1
done
>&2 echo "Database is ready!"

# --- Run Database Migrations ---
# Now that the DB is ready, we import the initial schema.
echo "Running initial schema import..."
mysql -h"db" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < /var/www/html/database.sql

# Then, we apply the user migration script.
echo "Applying user migration..."
mysql -h"db" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < /var/www/html/user_migration.sql

echo "Migrations complete."

# --- Start the Main Process ---
# Finally, execute the main command that the Docker image was supposed to run (in this case, start Apache).
exec "$@"