FROM nginx:mainline

ENV PATH /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/magento/bin

# copy magento.conf
COPY ./magento.conf /usr/share/nginx/html/magento/

# Create virtual.conf
# Use envsubst to set the hostnames

# Mount volume shared files
# Symlink to /media and /static