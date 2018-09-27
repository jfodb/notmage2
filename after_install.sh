#!/bin/bash
if [[ $(findmnt -m /usr/share/nginx/html/magento/pub/media) ]]; then
    echo "Mounted"
else
    sudo mount -t efs fs-1e74a656:/ /usr/share/nginx/html/magento/pub/media/
fi
sudo php /usr/share/nginx/html/magento/bin/magento setup:upgrade
sudo php /usr/share/nginx/html/magento/bin/magento setup:static-content:deploy
sudo chown -R apache:nginx /usr/share/nginx/html/magento

sudo rm -rf /usr/share/nginx/html/magento/var/cache/* /usr/share/nginx/html/magento/var/page_cache/* /usr/share/nginx/html/magento/var/composer_home/* /usr/share/nginx/html/magento/var/tmp/*
sudo php /usr/share/nginx/html/magento/bin/magento setup:di:compile
sudo php /usr/share/nginx/html/magento/bin/magento cache:clean
