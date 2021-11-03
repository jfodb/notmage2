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
# chown -R nginx:nginx $MAGENTO/*
cd $MAGENTO && find var generated vendor pub/static pub/media app/etc -type f -exec chmod g+w {} + && find var generated vendor pub/static pub/media app/etc -type d -exec chmod g+ws {} + && chown -R nginx:nginx . && chmod u+x bin/magento

# Check to see if we need
# to re-create symlink to EFS
# mount
media_link=$MAGENTO/pub/media
if [ -L ${media_link} ] ; then
    if [ -e ${media_link} ] ; then
        echo "Symlink already exists"
    else
        echo "Broken symlink for media folder"
    fi
    elif [ -e ${media_link} ] ; then
    echo "Not a link, removing existing media"
    rm -rf $MAGENTO/pub/media
    ln -s /mnt/efs/fs1/media $MAGENTO/pub/
else
    echo "Creating symlink"
    ln -s /mnt/efs/fs1/media $MAGENTO/pub/
fi

/opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl -a stop
/opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl -a start
