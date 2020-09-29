#!/bin/sh
set -eo pipefail
COMMAND="$@"

# Override the default command
if [ -n "${COMMAND}" ]; then
  echo "ENTRYPOINT: Executing override command"
  exec $COMMAND
else
  /app/bin/magento setup:static-content:deploy
  chown -R nginx:nginx /app
  /opt/remi/php72/root/sbin/php-fpm -R --fpm-config /etc/php-fpm.d/www.conf; 2>&1 &
  /usr/sbin/nginx -c /etc/nginx/nginx.conf -g "daemon off;" 2>&1
  fg %1
fi
