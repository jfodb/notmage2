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

#grant ec2 access to code and logs
usermod -a -G nginx,apache ec2-user


#clean stale nginx logs
rm /usr/share/nginx/html/magento-access.log /usr/share/nginx/html/magento-error.log
# if nginx is not restarted or started after this, we have a problem. It doesn't handle having its log files taken away

# Install AWS CloudFront logging
sudo yum install -y awslogs

# Pull from S3 based on deployment group
aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/env.php /usr/share/nginx/html/magento/app/etc/env.php
aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/virtual.conf /etc/nginx/conf.d/virtual.conf
aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/cloudwatch/awslogs.conf /etc/awslogs/awslogs.conf

#build and deploy
php /usr/share/nginx/html/magento/bin/magento setup:upgrade
php /usr/share/nginx/html/magento/bin/magento setup:di:compile
php /usr/share/nginx/html/magento/bin/magento setup:static-content:deploy
php /usr/share/nginx/html/magento/bin/magento index:reindex

#building makes bad cache
php /usr/share/nginx/html/magento/bin/magento cache:clean
#a few more
rm -rf /usr/share/nginx/html/magento/var/composer_home/* /usr/share/nginx/html/magento/var/tmp/* /usr/share/nginx/html/magento/var/log/system.log

# Turn on AWS CloudFront logging
sudo service awslogs start
sudo chkconfig awslogs on

chown -R apache:nginx /usr/share/nginx/html/magento

#reload file changes and flushed logs
service nginx reload
