# mage2donations
codebase to build donations platform using Magento 2.

## Local development with docker
* Clone repository
* run `docker-compose up -d` to initialize containers.
* run `docker exec mage2donations_web_1 magento setup:install --base-url=http://dev.mage2.org --db-host=mysql --db-name=magento --db-user=magento_user --db-password=magento --admin-firstname=Magento --admin-lastname=User --admin-email=user@example.com --admin-user=admin --admin-password=admin123 --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1` to install magento 2 and initialize the database.
* copy contents of `docker/env.php` to `app/etc/env.php`
* update local `/etc/hosts` to point `dev.mage2.org` to `127.0.0.1`
* start hacking away.
