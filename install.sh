#!/bin/bash
if [ ! -d /usr/share/nginx/html/magento ]; then
    mkdir /usr/share/nginx/html/magento;
fi
if [[ $(findmnt -m /usr/share/nginx/html/magento/pub/media) ]]; then
    umount /usr/share/nginx/html/magento/pub/media
fi

# Copy env to temp
cp /usr/share/nginx/html/magento/app/etc/env.php /tmp/env.php
cp /usr/share/nginx/html/magento/magento.conf /tmp/magento.conf 

# Remove existing code for auto-scaling purposes
rm -rf /usr/share/nginx/html/magento