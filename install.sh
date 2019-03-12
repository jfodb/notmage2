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

#pump history file
echo "mysql -h donations-beta.cluster-cvcm4uujid2s.us-east-1.rds.amazonaws.com -u mage2 -p mage2" >> /home/ec2-user/.bash_history
echo "mysql -h donations-production-serverless.cluster-cvcm4uujid2s.us-east-1.rds.amazonaws.com -u mage2 -p mage2" >> /home/ec2-user/.bash_history
echo "tail -200 /usr/share/nginx/html/magento-access.log | less" >> /home/ec2-user/.bash_history
echo "tail -200 /usr/share/nginx/html/magento-error.log | less" >> /home/ec2-user/.bash_history
echo "tail -200 /usr/share/nginx/html/magento/var/log/system.log | less" >> /home/ec2-user/.bash_history
echo "rm -rf var/page_cache/* var/cache/*" >> /home/ec2-user/.bash_history
echo "/usr/share/nginx/html/magento" >> /home/ec2-user/.bash_history
echo "grep " 400\|500 " /usr/share/nginx/html/magento-access.log | grep -v "CloudFront" | less" >> /home/ec2-user/.bash_history


#make a rebuild script outside of the magento html dir
echo "sudo rm -rf magento/pub/static/*" >> /usr/share/nginx/html/magentorebuild.sh
echo "sudo rm -rf magento/var/page_cache/*" >> /usr/share/nginx/html/magentorebuild.sh
echo "sudo rm -rf magento/var/cache/*" >> /usr/share/nginx/html/magentorebuild.sh
echo "sudo rm -rf magento/var/generation/*" >> /usr/share/nginx/html/magentorebuild.sh
echo "sudo rm -rf magento/generated/*" >> /usr/share/nginx/html/magentorebuild.sh
echo "php magento/bin/magento setup:upgrade" >> /usr/share/nginx/html/magentorebuild.sh
echo "php magento/bin/magento setup:di:compile" >> /usr/share/nginx/html/magentorebuild.sh
echo "php magento/bin/magento setup:static-content:deploy" >> /usr/share/nginx/html/magentorebuild.sh
echo "sudo rm -rf magento/var/page_cache/*" >> /usr/share/nginx/html/magentorebuild.sh
echo "sudo rm -rf magento/var/cache/*" >> /usr/share/nginx/html/magentorebuild.sh
echo "sudo chown -R apache:nginx magento/*" >> /usr/share/nginx/html/magentorebuild.sh
#set owner and permissions
chown ec2-user:ec2-user /usr/share/nginx/html/magentorebuild.sh
chmod u+x /usr/share/nginx/html/magentorebuild.sh
