#!/bin/bash
MAGENTO=/usr/share/nginx/html/magento

# Set group as nginx for file creation in php-fpm
sed -i s'/group = apache/group = nginx/' /etc/php-fpm.d/www.conf
service php-fpm restart

# Moved to after_install.sh because it seemed to be blocking deployments: 'Script at specified location: /application_start.sh failed to complete in 3600 seconds'
# chown -R apache:nginx $MAGENTO/*

# Permissions fix for Admin Export tool
# For future permissions changes, consider this resource first:
# https://docs.google.com/document/d/1bqAS6VZtT2uxWYbz5bqJW5s3zBrU2k7rcExqGdeRDqw/edit?usp=sharing
find $MAGENTO/var -type f -exec chmod g+w {} +
find $MAGENTO/var -type d -exec chmod g+ws {} +

chown root:root /etc/logrotate.d/magentorotate
chmod 440 /etc/logrotate.d/magentorotate

nginx -t

service nginx restart
