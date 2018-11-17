#!/bin/bash
exec 2> /tmp/after_install.log

if [[ $(findmnt -m /usr/share/nginx/html/magento/pub/media) ]]; then
    echo "Mounted"
else
    if [ "$DEPLOYMENT_GROUP_NAME" == "donations-production" ]
    then
        mount -t efs fs-e12571ab:/ /usr/share/nginx/html/magento/pub/media/
    else 
        mount -t efs fs-1e74a656:/ /usr/share/nginx/html/magento/pub/media/
    fi
    
fi

cp /tmp/env.php /usr/share/nginx/html/magento/app/etc/env.php
cp /tmp/magento.conf /usr/share/nginx/html/magento/magento.conf

php /usr/share/nginx/html/magento/bin/magento setup:upgrade
php /usr/share/nginx/html/magento/bin/magento setup:static-content:deploy

chown -R apache:nginx /usr/share/nginx/html/magento

rm -rf /usr/share/nginx/html/magento/var/cache/* /usr/share/nginx/html/magento/var/page_cache/* /usr/share/nginx/html/magento/var/composer_home/* /usr/share/nginx/html/magento/var/tmp/*
php /usr/share/nginx/html/magento/bin/magento setup:di:compile
php /usr/share/nginx/html/magento/bin/magento cache:clean

chown -R apache:nginx /usr/share/nginx/html/magento