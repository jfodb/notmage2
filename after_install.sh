#!/bin/bash
if [[ $(findmnt -m /usr/share/nginx/html/magento/pub/media) ]]; then
    echo "Mounted"
else
    mount -t efs fs-1e74a656:/ /usr/share/nginx/html/magento/pub/media/
fi

php /usr/share/nginx/html/magento/bin/magento setup:upgrade
php /usr/share/nginx/html/magento/bin/magento setup:static-content:deploy
chown -R apache:nginx /usr/share/nginx/html/magento

rm -rf /usr/share/nginx/html/magento/var/cache/* /usr/share/nginx/html/magento/var/page_cache/* /usr/share/nginx/html/magento/var/composer_home/* /usr/share/nginx/html/magento/var/tmp/*
php /usr/share/nginx/html/magento/bin/magento setup:di:compile
php /usr/share/nginx/html/magento/bin/magento cache:clean
