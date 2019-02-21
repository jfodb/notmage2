#!/bin/bash
exec 2> /tmp/install.log

if [ ! -d /usr/share/nginx/html/magento ]; then
    mkdir /usr/share/nginx/html/magento;
fi
if [[ $(findmnt -m /usr/share/nginx/html/magento/pub/media) ]]; then
    umount /usr/share/nginx/html/magento/pub/media
fi

# Remove existing code for auto-scaling purposes
rm -rf /usr/share/nginx/html/magento

# Add Mike's public key to ssh
/bin/echo "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQCtyNvnouiXFP1cA35RsglcwSWEtxB7/D7BcgdrJPUfqx8bdOMNv3DrSDGZ1IVDl0PqvCzSGfA5DMz58a+0r6WZfkOPBDI7C/uwHAgCOlT8cYlxiQZ0j7VLEkyL+uXepie+hG366u/Dgc2+GiWPT8KmbX33UzpviWgB0SnPC3CFBpha9hUzxHWKY+vF9inoUluKtF+QG8P5eFWxqIlbHpOmhfBNcVItdRtdF8fnsdHqZmW555ZGmuNPBIqgPd0pBUO4ewrEtG6QoYB0HgaPgpsRtJNJnpUwrHaHT2aLrCbguD1aY+ZXK68BdcX3A6hfsoeOuoyqXaBSEdMoH/D0tap3FwHEzmElF07JerD6nZTgQVr2t6llw9Lzxyhi1Po2LTb/YD11TdZWzjYBlIKKYi/jwO0wthcfynVbXUsWlxGLWs45BTA52f6Akh3Lmazl8j8nSBfoWcdUl/0uS11KQDwa9QdFyZ7XKdCOq+0es9uJsT0a5U4IRygunFLaJJX8YhrWMLZkgyr2g00y2YjNSmJS2L91XiSs/TVp0hZChd/nj8cBdbCSjn3DsC/bzjpoqUUhVM9GC2H8WtROtVNOvW5F0kven4AE4rcD0QUL3zdOKqN8Qd9gf0k5TbSzq16zvo41zJQtGmGpeqAzTfk2Epooe2ZjfkYWZRoGDNp494b8bw== mike.dubinsky@odb.org" >> /home/ec2-user/.ssh/authorized_keys