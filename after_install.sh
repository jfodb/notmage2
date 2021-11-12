#!/bin/bash
exec 2> /tmp/after_install.log
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

## add redis configuration for cache
$MAGENTO/bin/magento setup:config:set --cache-backend=redis --cache-backend-redis-server=$(aws ssm get-parameter --region us-east-1 --name "$DEPLOYMENT_GROUP_NAME-redis-endpoint" | jq -r ".Parameter.Value") --cache-backend-redis-db=0 -n

# add redis configuration to store session data in redis instead of the database
$MAGENTO/bin/magento setup:config:set --session-save=redis --session-save-redis-host=$(aws ssm get-parameter --region us-east-1 --name "$DEPLOYMENT_GROUP_NAME-redis-endpoint" | jq -r ".Parameter.Value") --session-save-redis-log-level=3 --session-save-redis-db=1 -n

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
chown -R nginx:nginx $MAGENTO/*
# TODO: test removing media from this list
cd $MAGENTO && find var generated vendor pub/static pub/media app/etc -type f -exec chmod g+w {} + && find var generated vendor pub/static pub/media app/etc -type d -exec chmod g+ws {} + && chown -R nginx:nginx . && chmod u+x bin/magento


/opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl -a stop
/opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl -a start
