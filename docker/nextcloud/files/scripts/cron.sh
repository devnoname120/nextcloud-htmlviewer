#!/usr/bin/env bash

cd /var/www/html/ || exit;

while true
do
  sleep 1m;
  echo "running cron…";
  php cron.php;
done;