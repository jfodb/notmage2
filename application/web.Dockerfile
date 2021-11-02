FROM nginx:mainline

ENV PATH /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/magento/bin

# copy magento.conf
COPY ./magento.conf /usr/share/nginx/html/magento/

# Create virtual.conf
# Use envsubst to set the hostnames
RUN apt-get install gettext-base

COPY ./virtual.conf.sample /tmp/virtual.conf
RUN envsubst '$STAGE' < /tmp/virtual.conf > /etc/nginx/conf.d/virtual.conf

# Mount volume shared files
# Symlink to /media and /static