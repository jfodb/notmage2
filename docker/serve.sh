#!/bin/bash

mkdir -p /etc/service/nginx
cp /magento/docker/runit-nginx.sh /etc/service/nginx/run

mkdir -p /etc/service/php7-fpm
cp /magento/docker/runit-phpfpm.sh /etc/service/php7-fpm/run

exec /sbin/my_init
