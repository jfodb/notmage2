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

#Add Edward's public key to ssh
/bin/echo "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDKds26Z1M3hG6anCQs4o8Akz0z5xX0WUBC9sFReszjCDnXhHfZQiQEPBF7spcWvrT8TT4ZAB0er2O5ZUmJZjI4cspHv0TJz9rxUyrRNMiY6HBRcoZaD6E3/1bMT+MPNMFIK8eJD1CjIsFHtB3vuqQEeC1njVxZA6kZ7j+VWUZlK5/csSbhVaDsagunAtKxLzI+VnucRIdSnrhGdLMVySL3FE0PGH99cfPQ7qL8sP3NkirUQDRPdI9f+Ox2Dd2cy1npnanOUw8m6vE+x/ewBRvWJtAUeyDMFuhvyd7M4tyZtO3B7JT1lqSmWBoS5wkLO3FggD5MHIWsMxzy2xUOyhXv edwardolsen@eolsen-mbp.local" >> /home/ec2-user/.ssh/authorized_keys

#Add Chris' public key to ssh
/bin/echo "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDItEGF6m9L6SRKjDo1dy5eDbJ/Me0bjmFAXFeWIabNfwVWjFrKMgSdiPK+QF9EdMsFWep1d5hMio7xStyl9P7ldKOb2cI6dobuoX5hWkaCbcDbhv7F+0Y5DJFRPFajogPnTLpt74ubrYdagvIdE+B/iDV3vCBq1HfF3v9Sk+tH0RoErBgSfKbM8vVUAJQuFLDTx7hQShcxImgI08LepXWuxcBrTEOE0y9/HEBM1/5QM/rW4GFT2ml7uAAFaoItmTiNAPUivjBOuWrLQ4HwFFBprxoAyGEOG0OTgYGOXOJQt2ThpGQvHh8tbqv0lAWAgrg8wxf4dGrGJIQSOshTuiGr chriswatts@cwatts-mbp.local"  >> /home/ec2-user/.ssh/authorized_keys

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
