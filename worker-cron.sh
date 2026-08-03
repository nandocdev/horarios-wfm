#!/bin/bash

APP_DIR="/var/www/wfm-scheduler"
END_HOUR=19

cd "$APP_DIR" || exit 1

while true; do
  CURRENT_HOUR=$(date +%H)

  if [ "$CURRENT_HOUR" -ge "$END_HOUR" ]; then
    echo "Reached end hour. Exiting..."
    exit 0
  fi

  # Tu comando real aquí
  php artisan cisco:sync --loop --isolated=1

  sleep 5
done
