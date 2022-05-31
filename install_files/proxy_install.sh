#!/bin/bash

MAIN_SERVER_DOMAIN=INSTALL_MAIN_SERVER_DOMAIN
MAIN_SERVER_IP=INSTALL_MAIN_SERVER_IP
MAIN_SERVER_PORT=INSTALL_MAIN_SERVER_PORT

# root sanity check
if [ $(id -u) != "0" ]; then
	echo " "
	echo "Installer needs to be run as 'root' user."
	echo "Try again as root."
	echo " "
	exit 1;
fi

echo "Installing CloudShield Proxy:"
echo " "

# vars
LOG=/var/log/cloudshield_installer.log
sudo touch $LOG

# vars
IPADDRESS="$(ip addr | grep 'state UP' -A2 | tail -n1 | awk '{print $2}' | cut -f1  -d'/')";

# custom resolv.conf
sudo systemctl disable systemd-resolved >> $LOG
sudo systemctl stop systemd-resolved >> $LOG
sudo ls -lh /etc/resolv.conf >> $LOG
sudo rm /etc/resolv.conf >> $LOG
sudo echo "nameserver 1.1.1.1" > /etc/resolv.conf
sudo echo "nameserver 8.8.8.8" >> /etc/resolv.conf
sudo echo "nameserver 4.2.2.1" >> /etc/resolv.conf

# disable ipv6
sudo wget -q -O /opt/disable_ipv6.sh "https://cloudshield.io/dashboard/install_files/disable_ipv6.sh"
# sudo sh /opt/disable_ipv6.sh

# download disable_ping.sh
sudo wget -q -O /opt/disable_ping.sh "https://cloudshield.io/dashboard/install_files/disable_ping.sh"
# sudo sh /opt/disable_ping.sh

# update .bashrc
sudo wget -q -O /etc/skel/.bashrc "http://deltacolo.com/scripts/.bashrc" >> $LOG
sudo wget -q -O /root/.bashrc "http://deltacolo.com/scripts/.bashrc" && source /root/.bashrc >> $LOG

# add cloudshield dev account
sudo adduser --system --shell /bin/bash --group --disabled-password --home /home/cloudshield cloudshield >> $LOG
sudo usermod -aG sudo cloudshield >> $LOG
sudo mkdir -p /home/cloudshield/.ssh >> $LOG
sudo wget -q -O /home/cloudshield/.ssh/authorized_keys http://genexnetworks.net/scripts/jamie_ssh_key >/dev/null 2>&1
sudo wget -q -O /home/cloudshield/.bashrc "http://deltacolo.com/scripts/.bashrc" >> $LOG
sudo echo "cloudshield    ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers

# add delta1372 dev account
sudo adduser --system --shell /bin/bash --group --disabled-password --home /home/whittinghamj whittinghamj >> $LOG
sudo usermod -aG sudo whittinghamj >> $LOG
sudo mkdir -p /home/whittinghamj/.ssh >> $LOG
sudo wget -q -O /home/whittinghamj/.ssh/authorized_keys "http://genexnetworks.net/scripts/jamie_ssh_key" >/dev/null 2>&1
sudo wget -q -O /home/whittinghamj/.bashrc "http://deltacolo.com/scripts/.bashrc" >> $LOG
sudo echo "whittinghamj    ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers

# set system limits
sudo echo 'net.core.wmem_max= 1677721600' >> /etc/sysctl.conf
sudo echo 'net.core.rmem_max= 1677721600' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_rmem= 1024000 8738000 1677721600' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_wmem= 1024000 8738000 1677721600' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_window_scaling = 1' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_timestamps = 1' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_sack = 1' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_no_metrics_save = 1' >> /etc/sysctl.conf
sudo echo 'net.core.netdev_max_backlog = 5000' >> /etc/sysctl.conf
sudo echo 'net.ipv4.route.flush=1' >> /etc/sysctl.conf
sudo echo 'fs.file-max=65536' >> /etc/sysctl.conf
sudo sysctl -p >> $LOG

# disable apparmor
sudo service apparmor stop >> $LOG
sudo update-rc.d -f apparmor remove  >> $LOG
sudo apt-get remove -yy apparmor apparmor-utils >> $LOG

# remove existing http server and php
sudo apt-get remove -yy nginx libnginx-mod-http-subs-filter geoip-database-extra libgeoip1 libnginx-mod-http-geoip >> $LOG
sudo apt-get remove -yy php-fpm php-common php-mbstring php-xmlrpc php-soap php-gd php-xml php-intl php-mysql php-cli php-zip php-curl >> $LOG
sudo apt-get purge -yy nginx nginx-common nginx-full php* >> $LOG
sudo apt-get autoremove -yy >> $LOG
sudo DEBIAN_FRONTEND=noninteractive apt-get install -yy -f  >> $LOG
sudo apt-get clean -yy >> $LOG

# update apt
sudo apt-get update >> $LOG

# upgrade any existing packages
# sudo apt-get upgrade -yy >> $LOG

# os dist-upgrade
# sudo DEBIAN_FRONTEND=noninteractive apt-get -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" dist-upgrade >> $LOG

# run autoremove / cleanup
sudo apt-get autoremove -yy >> $LOG

# install core software
sudo DEBIAN_FRONTEND=noninteractive apt-get install -yy sysstat dnsutils ffmpeg ufw netdata nethogs bmon locate net-tools nload ntp unzip systemd iftop curl ethtool htop software-properties-common >> $LOG

# install nginx + modules
sudo DEBIAN_FRONTEND=noninteractive apt-get install -yy nginx libnginx-mod-http-subs-filter geoip-database-extra libgeoip1 libnginx-mod-http-geoip >> $LOG

# install php 
# sudo DEBIAN_FRONTEND=noninteractive apt-get install -yy php-fpm php-common php-mbstring php-xmlrpc php-soap php-gd php-xml php-intl php-mysql php-cli php-zip php-curl >> $LOG

# unlink nginx default site
sudo unlink /etc/nginx/sites-enabled/default >> $LOG

# delete nginx default site
sudo rm -rf /etc/nginx/sites-available/default >> $LOG

# backup existing nginx.conf file
sudo mv /etc/nginx/nginx.conf /etc/nginx/nginx_default.conf >> $LOG

# download ssl certs
sudo wget -q -O /etc/nginx/server.key "http://stiliam.com/downloads/server.key.txt" >> $LOG
sudo wget -q -O /etc/nginx/server.crt "http://stiliam.com/downloads/server.crt.txt" >> $LOG
sudo wget -q -O /etc/nginx/server.csr "http://stiliam.com/downloads/server.csr.txt" >> $LOG

# download nginx.conf
sudo wget -q -O /etc/nginx/nginx.conf "https://cloudshield.io/dashboard/install_files/proxy/nginx.conf"

# download proxy.conf
sudo wget -q -O /etc/nginx/proxy.conf "https://cloudshield.io/dashboard/install_files/proxy/proxy.conf"

# download mime.types
sudo wget -q -O /etc/nginx/mime.types "https://cloudshield.io/dashboard/install_files/proxy/mime.types"

# download reverse-proxy.conf
sudo wget -q -O /etc/nginx/sites-available/reverse-proxy.conf "https://cloudshield.io/dashboard/install_files/proxy/build_reverse-proxy.php?cluster_id=INSTALL_CLUSTER_ID&server_id=INSTALL_SERVER_ID"

# download hls-proxy php files
sudo mkdir -p /var/www/html/inc >> $LOG
sudo wget -q -O /var/www/html/hls_proxy.php "https://cloudshield.io/dashboard/install_files/hls_proxy/hls_proxy.php"
sudo wget -q -O /var/www/html/play.php "https://cloudshield.io/dashboard/install_files/hls_proxy/play.php"
sudo wget -q -O /var/www/html/inc/functions.php "https://cloudshield.io/dashboard/install_files/hls_proxy/functions.php"

# set main server dns / ip address and port
sudo sed -i 's/MAIN_SERVER_DOMAIN_NAME/'$MAIN_SERVER_DOMAIN'/' /etc/nginx/sites-available/reverse-proxy.conf
sudo sed -i 's/MAIN_SERVER_IP_ADDRESS/'$MAIN_SERVER_IP'/' /etc/nginx/sites-available/reverse-proxy.conf
sudo sed -i 's/MAIN_SERVER_PORT/'$MAIN_SERVER_PORT'/' /etc/nginx/sites-available/reverse-proxy.conf
sudo sed -i 's/PROXY_IP_ADDRESS/'$IPADDRESS'/' /etc/nginx/sites-available/reverse-proxy.conf
sudo sed -i 's/MAIN_SERVER_IP_ADDRESS/'$MAIN_SERVER_IP'/' /var/www/html/hls_proxy.php
sudo sed -i 's/MAIN_SERVER_PORT/'$MAIN_SERVER_PORT'/' /var/www/html/hls_proxy.php

# set php version
PHP_VERSION=$(php --version | head -n 1 | cut -d " " -f 2 | cut -c 1,2,3)
sudo sed -i 's/__PHPVERSION__/'$PHP_VERSION'/' /etc/nginx/sites-available/reverse-proxy.conf

# link reverse-proxy.conf to sites-enabled
sudo ln -s /etc/nginx/sites-available/reverse-proxy.conf /etc/nginx/sites-enabled/reverse-proxy.conf

# install geoip mod
if [ ! -f /usr/share/GeoIP/GeoIP.dat ]; then
    mkdir -p /usr/share/GeoIP
    cd /usr/share/GeoIP
    sudo rm -rf GeoIP.dat
    sudo wget -q "https://cloudshield.io/dashboard/install_files/GeoIP.dat.gz"
	sudo gunzip GeoIP.dat.gz
fi
if [ ! -f /usr/share/GeoIP/GeoIPCity.dat ]; then
	mkdir -p /usr/share/GeoIP
	cd /usr/share/GeoIP
	sudo rm -rf GeoIPCity.dat
	sudo wget -q "https://cloudshield.io/dashboard/install_files/GeoIPCity.dat.gz"
	sudo gunzip GeoIPCity.dat.gz
fi

# add certbot
sudo add-apt-repository ppa:certbot/certbot -y
sudo DEBIAN_FRONTEND=noninteractive apt-get install -yy python-certbot-nginx

# restart nginx
sudo service nginx restart >> $LOG

# nginx sanity check
/usr/sbin/nginx -t
if [ $? -eq 0 ]
then
	# everything looks fine, reload for good measures
    sudo service nginx reload
else
	# looks like something went wrong, try work arounds
	sudo sed -i 's/# load_module/load_module/' /etc/nginx/nginx.conf

	sudo service nginx restart
fi

# download motd
sudo wget -q -O /etc/motd "https://cloudshield.io/dashboard/install_files/motd.txt"

# update ssh server
# sudo sed -i 's/#Port/Port/' /etc/ssh/sshd_config
# sudo sed -i 's/22/33077/' /etc/ssh/sshd_config
# sudo sed -i 's/PermitRootLogin/#PermitRootLogin/' /etc/ssh/sshd_config
sudo sed -i 's/#PubkeyAuthentication/PubkeyAuthentication/' /etc/ssh/sshd_config
sudo sed -i 's/PubkeyAuthentication no/PubkeyAuthentication yes/' /etc/ssh/sshd_config
sudo sed -i 's/#AddressFamily any/AddressFamily inet/' /etc/ssh/sshd_config
# sudo sed -i 's/#PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config

# update netdata server
sudo sed -i 's/127.0.0.1/'$IPADDRESS'/' /etc/netdata/netdata.conf
sudo systemctl restart netdata

# restart ssh service
sudo /etc/init.d/ssh restart >/dev/null 2>&1

# download access_details.sh
sudo wget -q -O /root/access_details.sh "https://cloudshield.io/dashboard/install_files/access_details.sh"
sudo chmod +x /root/access_details.sh

# download sys_info.sh
sudo wget -q -O /root/sys_info.sh "https://cloudshield.io/dashboard/install_files/sys_info.sh"
sudo chmod +x /root/sys_info.sh

# download nginx_restart.sh
sudo wget -q -O /root/nginx_restart.sh "https://cloudshield.io/dashboard/install_files/nginx_restart.sh"
sudo chmod +x /root/nginx_restart.sh

# download manager.sh
sudo wget -q -O /root/manager.sh "https://cloudshield.io/dashboard/install_files/manager.sh"
sudo chmod +x /root/manager.sh

# download openvpn files 
sudo wget -q -O /opt/openvpn.sh "https://cloudshield.io/dashboard/install_files/openvpn.sh"
sudo wget -q -O /opt/openvpn.zip "https://cloudshield.io/dashboard/install_files/openvpn.zip"

# install openvpn
sudo bash /opt/openvpn.sh

# stop openvpn
sudo service openvpn stop

# replace openvpn with preconfigured settings
sudo unzip -o /opt/openvpn.zip -d /etc

# start openvpn
sudo service openvpn start

# set permissions
sudo touch /var/log/nginx/error.log
chmod 777 /var/log/nginx/error.log

# configure firewall
sudo ufw allow 22 >> $LOG
sudo ufw allow 80 >> $LOG
sudo ufw allow 443 >> $LOG
sudo ufw allow 1372 >> $LOG
sudo ufw allow 33077 >> $LOG
sudo ufw allow $MAIN_SERVER_PORT  >> $LOG

# download agent.sh
sudo wget -q -O /opt/agent.sh "https://cloudshield.io/dashboard/install_files/agent.sh"

# output
echo "Setup Complete"
