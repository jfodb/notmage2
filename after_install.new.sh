#!/bin/bash
exec 2> /tmp/after_install.log

MAGENTO=/usr/share/nginx/html/magento

# if [[ $(findmnt -m $MAGENTO/pub/media) ]]; then
#     echo "Mounted"
# else
#     if [ "$DEPLOYMENT_GROUP_NAME" == "donations-production" ]
#     then
#         mount -t efs fs-1e74a656:/ $MAGENTO/pub/media/
#     else
#         mount -t efs fs-e12571ab:/ $MAGENTO/pub/media/
#     fi
# fi

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

#grant ec2 access to code and logs
usermod -a -G nginx,apache ec2-user

# Pull from S3 based on deployment group
cp $MAGENTO/app/etc/env.php.sample $MAGENTO/app/etc/env.php
perl -pi -e s/$(echo odb_db_host)/$(aws ssm get-parameter --region us-east-1 --name "$DEPLOYMENT_GROUP_NAME-host" | jq -r ".Parameter.Value")/g $MAGENTO/app/etc/env.php
perl -pi -e s/$(echo odb_db_password)/$(aws secretsmanager get-secret-value --region us-east-1 --secret-id $DEPLOYMENT_GROUP_NAME-credentials | jq -r '.SecretString' | jq -r '.password')/g $MAGENTO/app/etc/env.php
perl -pi -e s/$(echo odb_db_user)/$(aws secretsmanager get-secret-value --region us-east-1 --secret-id $DEPLOYMENT_GROUP_NAME-credentials | jq -r '.SecretString' | jq -r '.username')/g $MAGENTO/app/etc/env.php

$MAGENTO/bin/magento setup:config:set --cache-backend=redis --cache-backend-redis-server=$(aws ssm get-parameter --region us-east-1 --name "$DEPLOYMENT_GROUP_NAME-redis-endpoint" | jq -r ".Parameter.Value") --cache-backend-redis-db=0 -n
$MAGENTO/bin/magento setup:config:set --session-save=redis --session-save-redis-host=$(aws ssm get-parameter --region us-east-1 --name "$DEPLOYMENT_GROUP_NAME-redis-endpoint" | jq -r ".Parameter.Value") --session-save-redis-log-level=3 --session-save-redis-db=1 -n

# set magento permissions
cd $MAGENTO && find var generated vendor pub/static pub/media app/etc -type f -exec chmod g+w {} + && find var generated vendor pub/static pub/media app/etc -type d -exec chmod g+ws {} + && chown -R nginx:nginx . && chmod u+x bin/magento

#build and deploy
php $MAGENTO/bin/magento setup:upgrade
php $MAGENTO/bin/magento setup:di:compile
php $MAGENTO/bin/magento deploy:mode:set production
php $MAGENTO/bin/magento setup:static-content:deploy en_US es_MX
php $MAGENTO/bin/magento index:reindex
php $MAGENTO/bin/magento cron:install

#building makes bad cache
php $MAGENTO/bin/magento cache:clean
