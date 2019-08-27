FROM phusion/baseimage

EXPOSE 80
EXPOSE 443

CMD ["/magento/docker/serve.sh"]

ENV PATH /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/magento/bin

RUN locale-gen --no-purge en_US.UTF-8
ENV LC_ALL en_US.UTF-8
ENV LANG en_US.UTF-8
ENV DEBIAN_FRONTEND noninteractive

RUN apt-add-repository -y ppa:ondrej/php \
    && apt-get update \
    && DEBIAN_FRONTEND=noninteractive \
       apt-get install -qqy --assume-yes --no-install-recommends \
                    php7.2-cli \
                    php7.2-common \
                    php7.2-fpm \
                    php7.2-mbstring \
                    php7.2-mysql \
                    php7.2-xml \
                    php7.2-gd \
                    php7.2-curl \
                    php7.2-intl \
                    php7.2-xsl \
                    php7.2-zip \
                    php7.2-bcmath \
                    php7.2-iconv \
                    php7.2-redis \
                    php7.2-soap \
                    nginx \
                    nano \
                    php-xdebug \
                    strace
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && export PATH=$PATH:/magento/bin \
	&& curl -sL https://deb.nodesource.com/setup_8.x | bash - \
	&& apt-get install -y nodejs \
	&& npm install -g grunt-cli

COPY ./docker/nginx.conf /etc/nginx/sites-available/default
COPY ./docker/xdebug.ini /etc/php/7.2/mods-available/xdebug.ini
COPY ./docker/nginx-selfsigned.key /etc/ssl/private/nginx-selfsigned.key
COPY ./docker/nginx-selfsigned.crt /etc/ssl/certs/nginx-selfsigned.crt
COPY ./docker/dhparam.pem /etc/ssl/certs/dhparam.pem
COPY ./docker/mage-signed.conf /etc/nginx/snippets/mage-signed.conf
COPY ./docker/ssl-params.conf /etc/nginx/snippets/ssl-params.conf
COPY ./docker/php.ini /etc/php/7.2/fpm/php.ini

COPY ./docker/env.php /magento/app/env.php

ADD . /magento
