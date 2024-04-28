#!/usr/bin/env bash

if [ ! -L /var/www/html/custom_apps/htmlviewer ]; then
  ln -s /app /var/www/html/custom_apps/htmlviewer
fi

/var/www/html/occ app:enable htmlviewer;

exit 0;