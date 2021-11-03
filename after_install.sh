#!/bin/bash
MAGENTO=/usr/share/nginx/html/magento

#grant ec2 access to code and logs
usermod -a -G nginx,apache ec2-user

# copy generated env.php file
cp $MAGENTO/app/etc/env.php.sample $MAGENTO/app/etc/env.php 

# set db host
perl -pi -e s/$(echo odb_db_host)/$(aws ssm get-parameter --region us-east-1 --name "$DEPLOYMENT_GROUP_NAME-host" | jq -r ".Parameter.Value")/g $MAGENTO/app/etc/env.php
# set db pass
perl -pi -e s/$(echo odb_db_password)/$(aws secretsmanager get-secret-value --region us-east-1 --secret-id $DEPLOYMENT_GROUP_NAME-credentials | jq -r '.SecretString' | jq -r '.password')/g $MAGENTO/app/etc/env.php
# set db user
perl -pi -e s/$(echo odb_db_user)/$(aws secretsmanager get-secret-value --region us-east-1 --secret-id $DEPLOYMENT_GROUP_NAME-credentials | jq -r '.SecretString' | jq -r '.username')/g $MAGENTO/app/etc/env.php

# aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/virtual.conf /etc/nginx/conf.d/virtual.conf
# deploy cloudwatch file
aws s3 cp s3://wp.shared-files/"$DEPLOYMENT_GROUP_NAME"/cloudwatch/ssm-donations /opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.d/ssm-donations
# delete now redundant beta or production file
rm /opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.d/ssm_donations-production
rm /opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.d/ssm_beta-donations


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
