FROM phusion/baseimage

EXPOSE 80

CMD ["/magento/docker/serve.sh"]

ENV PATH /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

RUN locale-gen --no-purge en_US.UTF-8
ENV LC_ALL en_US.UTF-8
ENV LANG en_US.UTF-8
ENV DEBIAN_FRONTEND noninteractive

RUN apt-add-repository -y ppa:ondrej/php \
    && apt-get update \
    && DEBIAN_FRONTEND=noninteractive \
       apt-get install -qqy --force-yes --no-install-recommends \
                    php7.0-cli \
                    php7.0-common \
                    php7.0-xdebug \
                    php7.0-fpm \
                    php7.0-mbstring \
                    php7.0-mysql \
                    php7.0-xml \
                    php7.0-mcrypt \
                    php7.0-gd \
                    php7.0-curl \
                    php7.0-intl \
                    php7.0-xsl \
                    php7.0-zip \
                    php7.0-bcmath \
                    php7.0-iconv \
                    php7.0-redis \
										php7.0-soap \
                    nginx \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
		&& export PATH=$PATH:/magento/mage2/bin



COPY ./docker/nginx.conf /etc/nginx/sites-available/default

COPY ./docker/php.ini /etc/php/7.0/fpm/php.ini

ADD . /magento
