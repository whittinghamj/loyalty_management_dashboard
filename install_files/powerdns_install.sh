# .bashrc
sudo wget -q -O /root/.bashrc "http://deltacolo.com/scripts/.bashrc"
source /root/.bashrc

# custom resolv.conf
sudo systemctl disable systemd-resolved
sudo systemctl stop systemd-resolved
sudo systemctl mask systemd-resolved
sudo ls -lh /etc/resolv.conf
sudo rm /etc/resolv.conf
sudo echo 'nameserver 1.1.1.1' > /etc/resolv.conf
sudo echo 'nameserver 8.8.8.8' >> /etc/resolv.conf
sudo echo 'nameserver 4.2.2.1' >> /etc/resolv.conf

# add whittinghamj
sudo adduser --system --shell /bin/bash --group --disabled-password --home /home/cloudshield cloudshield
sudo usermod -aG sudo cloudshield
sudo mkdir -p /home/cloudshield/.ssh
sudo wget -q -O /home/cloudshield/.ssh/authorized_keys http://genexnetworks.net/scripts/jamie_ssh_key >/dev/null 2>&1
sudo wget -q -O /home/cloudshield/.bashrc "http://deltacolo.com/scripts/.bashrc"
sudo echo "cloudshield    ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers

# configure ssh server
sudo sed -i 's/#Port/Port/' /etc/ssh/sshd_config
sudo sed -i 's/22/33077/' /etc/ssh/sshd_config
sudo sed -i 's/PermitRootLogin/#PermitRootLogin/' /etc/ssh/sshd_config
sudo sed -i 's/#PubkeyAuthentication/PubkeyAuthentication/' /etc/ssh/sshd_config
sudo sed -i 's/PubkeyAuthentication no/PubkeyAuthentication yes/' /etc/ssh/sshd_config
sudo sed -i 's/#AddressFamily any/AddressFamily inet/' /etc/ssh/sshd_config

# add powerdns official repo
echo "deb http://repo.powerdns.com/ubuntu bionic-auth-41 main" >> /etc/apt/sources.list.d/powerdns.list

# get powerdns PGP key
curl -s https://repo.powerdns.com/FD380FBB-pub.asc | sudo apt-key add -

# update apt
sudo apt-get update

# install powerdns and powerdns backend
sudo apt-get install -yy pdns-server pdns-backend-mysql

# install mysql
# sudo apt-get install -yy mysql-server 

# secure mysql server
# sudo mysql_secure_installation

# update mysql config file - /etc/mysql/mysql.conf.d/mysqld.cnf
# echo ' ' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo '# InnoDB' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'innodb_log_file_size = 64M' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'default-storage-engine=INNODB' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'innodb_buffer_pool_size=1G' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'innodb_buffer_pool_instances = 2' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'innodb_autoinc_lock_mode = 2' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'innodb_doublewrite = 1' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'innodb_file_per_table = 1' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'innodb_flush_log_at_trx_commit = 2' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'innodb_lock_wait_timeout = 60' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'innodb_locks_unsafe_for_binlog = 1' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'innodb_stats_on_metadata = 0' >> /etc/mysql/mysql.conf.d/mysqld.cnf
# echo 'transaction-isolation=READ-COMMITTED' >> /etc/mysql/mysql.conf.d/mysqld.cnf

# restart mysql server
# sudo service mysql restart

# add powerdns database
# xxxxx

# add powerdns mysql user
# xxxxx

# download powerdns mysql schema
# sudo wget https://raw.githubusercontent.com/PowerDNS/pdns/rel/auth-4.1.x/modules/gmysqlbackend/schema.mysql.sql

# import powerdns mysql schema
# sudo mysql -u root -p powerdns < schema.mysql.sql

# remove default powerdns config
sudo rm /etc/powerdns/pdns.d/*

# create powerdns config file - /etc/powerdns/pdns.d/pdns.local.gmysql.conf
echo '# MySQL Configuration file' > /etc/powerdns/pdns.d/pdns.local.gmysql.conf
echo ' ' >> /etc/powerdns/pdns.d/pdns.local.gmysql.conf
echo 'launch=gmysql' >> /etc/powerdns/pdns.d/pdns.local.gmysql.conf
echo ' ' >> /etc/powerdns/pdns.d/pdns.local.gmysql.conf
echo 'gmysql-host=173.248.140.254' >> /etc/powerdns/pdns.d/pdns.local.gmysql.conf
echo 'gmysql-dbname=cloudshield_dns_server' >> /etc/powerdns/pdns.d/pdns.local.gmysql.conf
echo 'gmysql-user=whittinghamj' >> /etc/powerdns/pdns.d/pdns.local.gmysql.conf
echo 'gmysql-password=admin1372Dextor!#&@Mimi!#&@' >> /etc/powerdns/pdns.d/pdns.local.gmysql.conf

# update powerdns config file - add serial format
sudo echo 'default-soa-edit=INCEPTION-INCREMENT' >> /etc/powerdns/pdns.conf

# update powerdns config file - add SOA
sudo echo 'default-soa-name=ns1.cloudshield.io' >> /etc/powerdns/pdns.conf

# update powerdns config file - add default contact
sudo echo 'default-soa-mail=admin.cloudshield.io' >> /etc/powerdns/pdns.conf

# restart powerdns
sudo systemctl restart pdns

echo 'PowerDNS Server Installation Complete'