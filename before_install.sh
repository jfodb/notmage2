#!/bin/bash
MAGENTO=/usr/share/nginx/html/magento

if [ ! -d $MAGENTO ]; then
    mkdir $MAGENTO;
fi
if [[ $(findmnt -m $MAGENTO/pub/media) ]]; then
    umount $MAGENTO/pub/media
fi

# Remove existing code for auto-scaling purposes
# rm -rf $MAGENTO
