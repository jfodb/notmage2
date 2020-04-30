# mage2donations
codebase to build donations platform using Magento 2.

## Local development with docker
* Clone repository
* run `docker-compose up -d` to initialize containers.
* run `docker exec -i mage2donations_web_1 bash -c "cd /magento && composer install"`
* run `docker exec mage2donations_web_1 magento setup:install --base-url=http://dev.ourdailybreadpublishing.org --db-host=mysql --db-name=magento --db-user=magento_user --db-password=magento --admin-firstname=Magento --admin-lastname=User --admin-email=user@example.com --admin-user=admin --admin-password=admin123 --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1 --use-secure=1 --use-secure-admin=1` to install magento 2 and initialize the database.
* copy contents of `docker/env.php` to `app/etc/env.php`
* update local `/etc/hosts` with:
<pre>    127.0.0.1       dev.ourdailybreadpublishing.org
    127.0.0.1       dev.dhespanol.org
    127.0.0.1       dev.store.ourdailybread.org
    127.0.0.1       dev.store.christianuniversity.org
    127.0.0.1       dev.donations.ourdailybread.org</pre>
* download database from: [here](https://drive.google.com/file/d/1MDQ_z5Jc4VNolwu7uzXLdIor-CGmdyI7/)
* in your terminal, `cd` into the directory with the downloaded database
* run `docker exec -i mage2donations_mysql_1 mysql -umagento_user -pmagento magento < magento2-odbp-dev.sql` 
* start hacking away.<br /><br />
If you can't log in to the admin at https://dev.ourdailybreadpublishing.org/odbmadmin<br />
Create an account:<br />
* Run `docker exec -it mage2donations_web_1 bash` and then `cd /magento && magento admin:user:create` 
