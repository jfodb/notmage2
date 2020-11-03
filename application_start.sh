#!/bin/bash
MAGENTO=/usr/share/nginx/html/magento

# Set group as nginx for file creation in php-fpm
sed -i s'/group = apache/group = nginx/' /etc/php-fpm.d/www.conf
service php-fpm restart

# chmod -R 775 $MAGENTO/var $MAGENTO/pub $MAGENTO/app/etc
chown -R apache:nginx $MAGENTO/*

nginx -t

service nginx restart
