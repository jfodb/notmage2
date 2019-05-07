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
yum install -y awslogs

# Pull from S3 based on deployment group
aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/env.php /usr/share/nginx/html/magento/app/etc/env.php
aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/virtual.conf /etc/nginx/conf.d/virtual.conf
aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/cloudwatch/awslogs.conf /etc/awslogs/awslogs.conf

# TODO: After 2.3.1 update, remove the m2-hotfixes directory from GitHub and appspec.yml. Delete the below install command
# Install the PRODSECBUG-2198 security patch (https://magento.com/security/patches/magento-2.3.1-2.2.8-and-2.1.17-security-update)
cd /usr/share/nginx/html/magento/
git apply m2-hotfixes/PRODSECBUG-2198-composer.patch
rm -rf m2-hotfixes
cd ~

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
service awslogs start
chkconfig awslogs on

chown -R apache:nginx /usr/share/nginx/html/magento

#reload file changes and flushed logs
service nginx reload