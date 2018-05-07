#!/bin/bash
if [[ $(findmnt -m /usr/share/nginx/html/magento/pub/media) ]]; then
    echo "Mounted"
else
    mount -t efs fs-1e74a656:/ /usr/share/nginx/html/magento/pub/media/
fi
