#!/bin/sh
exec /usr/sbin/php-fpm7.2 --nodaemonize -R --fpm-config /magento/docker/php-fpm.conf
