#!/bin/bash
MAGENTO=/usr/share/nginx/html/magento

# chmod -R 775 $MAGENTO/var $MAGENTO/pub $MAGENTO/app/etc
chown -R apache:nginx $MAGENTO/*

nginx -t

service nginx restart