#!/bin/bash
# Create apache user
useradd apache
usermod -a -G nginx apache

## switch to apache user to create magento folder
su apache

MAGENTO=/usr/share/nginx/html/magento

if [ ! -d $MAGENTO ]; then
    mkdir $MAGENTO;
fi
if [[ $(findmnt -m $MAGENTO/pub/media) ]]; then
    umount $MAGENTO/pub/media
fi

# Remove existing code for auto-scaling purposes
# rm -rf $MAGENTO
