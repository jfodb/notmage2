# mage2donations
codebase to build donations platform using Magento 2.

## Local development with docker
* ~ Already have a local Magento Dev? Skip to the /etc/hosts step ~
* Clone repository
* run `docker-compose up -d` to initialize containers.
* run `docker exec -i mage2donations_web_1 bash -c "cd /magento && composer install"`
* run `docker exec mage2donations_web_1 magento setup:install --base-url=http://dev.ourdailybreadpublishing.org --db-host=mysql --db-name=magento --db-user=magento_user --db-password=magento --admin-firstname=Magento --admin-lastname=User --admin-email=user@example.com --admin-user=admin --admin-password=admin123 --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1 --use-secure=1 --use-secure-admin=1` to install magento 2 and initialize the database.
* copy contents of `docker/env.php` to `app/etc/env.php`
* update local `sudo nano /etc/hosts` with:
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
<br /><br />
## Enable or disable xdebug?
* remove the semi-colon on the start of line 1 in /etc/php/7.2/mods-available/xdebug.ini to enable xdebug. 
  * Add the semi-colon like `;zend_extension=xdebug.so` to disable.
* add your local ip address to `/etc/php/7.2/mods-available/xdebug.ini` on the line with xdebug.remote_host=
  * You can find your local IP address in terminal with `ipconfig getifaddr en0`
* restart docker with `docker-compose stop` and then `docker-compose up -d`
* check to see if xdebug is enabled by running `docker exec -it mage2donations_web_1 php -v`
  * if it says "...with Xdebug..." then it is enabled
* proceed with configuring your IDE and browser for Xdebug (TODO: need to add more info) 
<br /><br />
## Check (and auto-fix) your code against Magento 2's coding standards:
* Evaluate a specific module with `vendor/bin/phpcs --standard=Magento2 app/code/Vendor/Module`
  * You can optionally target a specific file by appending its file path and extension
* Auto-fix a module or file (when applicable - this can be observed in the results of the previous command) `vendor/bin/phpcs --standard=Magento2 app/code/Vendor/Module`
* Export results to a file `vendor/bin/phpcs --standard=Magento2 app/code/Vendor/Module --report-file="code-report.txt"
`<br />
See [Github Repo](https://github.com/magento/magento-coding-standard) for more details 