#!/bin/bash

if [ $# -ne 1 ]; then
    echo "$0 youruser:yourgroup (ec2-user:ec2-user)"
    exit 1
fi


#check here
if [ ! -f bin/magento ]; then
	#maybe we are in the docker directory
	cd ..
fi


if [ ! -f bin/magento ]; then
    echo "magento CLI not found"
    exit 1;
fi


#terminate cached files and reduce scope of ownership
sudo rm -rf pub/static/*
sudo rm -rf var/page_cache/*
sudo rm -rf var/cache/*
sudo rm -rf var/generation/*
sudo rm -rf generated/*

#take ownership to conduct operations
sudo chown -R $1 *

#rebuild
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy

#return to web user
sudo chown -R apache:nginx *
 