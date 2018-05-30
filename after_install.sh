#!/bin/bash
if [[ $(findmnt -m /usr/share/nginx/html/magento/pub/media) ]]; then
    echo "Mounted"
else
    mount -t efs fs-1e74a656:/ /usr/share/nginx/html/magento/pub/media/
fi
php /usr/share/nginx/html/magento/bin/magento setup:upgrade
php /usr/share/nginx/html/magento/bin/magento setup:static-content:deploy
chown -R apache:nginx /usr/share/nginx/html/magento
php /usr/share/nginx/html/magento/bin/magento cache:clean
