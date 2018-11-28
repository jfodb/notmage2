#!/bin/bash
exec 2> /tmp/install.log

if [ ! -d /usr/share/nginx/html/magento ]; then
    mkdir /usr/share/nginx/html/magento;
fi
if [[ $(findmnt -m /usr/share/nginx/html/magento/pub/media) ]]; then
    umount /usr/share/nginx/html/magento/pub/media
fi

# Remove existing code for auto-scaling purposes
rm -rf /usr/share/nginx/html/magento