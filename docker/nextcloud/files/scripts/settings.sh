#!/usr/bin/env bash

if [ ! -f /var/www/html/version.php ]; then
    exit 0;
fi

# System Settings
/var/www/html/occ config:system:set loglevel --value=0 --type=int;
/var/www/html/occ config:system:set default_phone_region --value=DE --type=string;
/var/www/html/occ config:system:set maintenance_window_start --value=2 --type=int;

if [ ! -L /var/www/html/custom_apps/htmlviewer ]; then
  ln -s /app /var/www/html/custom_apps/htmlviewer
fi