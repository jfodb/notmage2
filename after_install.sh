#!/bin/bash
MAGENTO=/usr/share/nginx/html/magento

#grant ec2 access to code and logs
usermod -a -G nginx,apache ec2-user

# Pull from S3 based on deployment group
aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/env.php $MAGENTO/app/etc/env.php
aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/virtual.conf /etc/nginx/conf.d/virtual.conf
aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/cloudwatch/ssm_beta-donations /opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.d

#build and deploy
php $MAGENTO/bin/magento setup:upgrade
php $MAGENTO/bin/magento setup:di:compile
php $MAGENTO/bin/magento deploy:mode:set production
php $MAGENTO/bin/magento setup:static-content:deploy en_US es_MX
php $MAGENTO/bin/magento index:reindex
php $MAGENTO/bin/magento cron:install

#building makes bad cache
php $MAGENTO/bin/magento cache:clean

# Fix permissions/owners
chown -R apache:nginx $MAGENTO/*

if [[ $(findmnt -m $MAGENTO/pub/media) ]]; then
    echo "Mounted"
else
    if [ "$DEPLOYMENT_GROUP_NAME" == "donations-production" ]
    then
        mount -t efs fs-1e74a656:/ $MAGENTO/pub/media/
    else
        mount -t efs fs-e12571ab:/ $MAGENTO/pub/media/
    fi
fi

/opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl -a stop
/opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl -a start
