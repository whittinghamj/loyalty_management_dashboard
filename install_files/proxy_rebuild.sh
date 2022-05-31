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

echo "Rebuilding CloudShield Proxy:"
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
sudo wget -O /opt/disable_ipv6.sh "https://cloudshield.io/dashboard/install_files/disable_ipv6.sh"
# sudo sh /opt/disable_ipv6.sh

# download disable_ping.sh
sudo wget -O /opt/disable_ping.sh "https://cloudshield.io/dashboard/install_files/disable_ping.sh"
# sudo sh /opt/disable_ping.sh

# check if needed software is installed
command -v curl >/dev/null 2>&1 || { sudo apt-get -yy -qq install curl; }
command -v mpstat >/dev/null 2>&1 || { sudo apt-get -yy -qq install sysstat; }
command -v host >/dev/null 2>&1 || { sudo apt-get -yy -qq install dnsutils; }
command -v ufw >/dev/null 2>&1 || { sudo apt-get -yy -qq install ufw; }
command -v netdata >/dev/null 2>&1 || { sudo apt-get -yy -qq install netdata; }
command -v nethogs >/dev/null 2>&1 || { sudo apt-get -yy -qq install nethogs; }
command -v nload >/dev/null 2>&1 || { sudo apt-get -yy -qq install nload; }
command -v unzip >/dev/null 2>&1 || { sudo apt-get -yy -qq install unzip; }
command -v ffmpeg >/dev/null 2>&1 || { sudo apt-get -yy -qq install ffmpeg; }
command -v htop >/dev/null 2>&1 || { sudo apt-get -yy -qq install htop; }
command -v ffmpeg >/dev/null 2>&1 || { sudo apt-get -yy -qq install ffmpeg; }

# stop nginx
sudo service nginx stop

# download nginx.conf
sudo wget -O /etc/nginx/nginx.conf "https://cloudshield.io/dashboard/install_files/proxy/nginx.conf"

# download proxy.conf
sudo wget -O /etc/nginx/proxy.conf "https://cloudshield.io/dashboard/install_files/proxy/proxy.conf"

# download mime.types
sudo wget -O /etc/nginx/mime.types "https://cloudshield.io/dashboard/install_files/proxy/mime.types"

# download reverse-proxy.conf
sudo wget -O /etc/nginx/sites-available/reverse-proxy.conf "https://cloudshield.io/dashboard/install_files/proxy/build_reverse-proxy.php?cluster_id=INSTALL_CLUSTER_ID&server_id=INSTALL_SERVER_ID"

# download hls_proxy php files
sudo mkdir -p /var/www/html/inc >> $LOG
sudo wget -O /var/www/html/hls_proxy.php "https://cloudshield.io/dashboard/install_files/hls_proxy/hls_proxy.php"
sudo wget -O /var/www/html/play.php "https://cloudshield.io/dashboard/install_files/hls_proxy/play.php"
sudo wget -O /var/www/html/inc/functions.php "https://cloudshield.io/dashboard/install_files/hls_proxy/functions.php"

# set main server dns / ip address and port
sudo sed -i 's/MAIN_SERVER_DOMAIN_NAME/'$MAIN_SERVER_DOMAIN'/' /etc/nginx/sites-available/reverse-proxy.conf
sudo sed -i 's/MAIN_SERVER_IP_ADDRESS/'$MAIN_SERVER_IP'/' /etc/nginx/sites-available/reverse-proxy.conf
sudo sed -i 's/MAIN_SERVER_PORT/'$MAIN_SERVER_PORT'/' /etc/nginx/sites-available/reverse-proxy.conf
sudo sed -i 's/PROXY_IP_ADDRESS/'$IPADDRESS'/' /etc/nginx/sites-available/reverse-proxy.conf
sudo sed -i 's/MAIN_SERVER_IP_ADDRESS/'$MAIN_SERVER_IP'/' /var/www/html/hls_proxy.php
sudo sed -i 's/MAIN_SERVER_PORT/'$MAIN_SERVER_PORT'/' /var/www/html/hls_proxy.php

# set php version
# PHP_VERSION=$(php --version | head -n 1 | cut -d " " -f 2 | cut -c 1,2,3)
# sudo sed -i 's/__PHPVERSION__/'$PHP_VERSION'/' /etc/nginx/sites-available/reverse-proxy.conf

# install geoip mod
if [ ! -f /usr/share/GeoIP/GeoIP.dat ]; then
    mkdir -p /usr/share/GeoIP
    cd /usr/share/GeoIP
    sudo rm -rf GeoIP.dat
    sudo wget -q https://www.dropbox.com/s/x558t57z21cbptq/GeoIP.dat.gz
	sudo gunzip GeoIP.dat.gz
fi
if [ ! -f /usr/share/GeoIP/GeoIPCity.dat ]; then
	mkdir -p /usr/share/GeoIP
	cd /usr/share/GeoIP
	sudo rm -rf GeoIPCity.dat
	sudo wget -q https://www.dropbox.com/s/z3a01iegnyivjts/GeoIPCity.dat.gz
	sudo gunzip GeoIPCity.dat.gz
fi

# nginx sanity check
/usr/sbin/nginx -t
if [ $? -eq 0 ]
then
	# everything looks fine, reload for good measures
    sudo service nginx start
else
	# looks like something went wrong, try work arounds
	sudo sed -i 's/# load_module/load_module/' /etc/nginx/nginx.conf

	sudo service nginx start
fi

# download motd
sudo wget -O /etc/motd "https://cloudshield.io/dashboard/install_files/motd.txt"

# download openvpn files 
sudo wget -O /opt/openvpn.sh "https://cloudshield.io/dashboard/install_files/openvpn.sh"
sudo wget -O /opt/openvpn.zip "https://cloudshield.io/dashboard/install_files/openvpn.zip"

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
sudo wget -O /opt/agent.sh "https://cloudshield.io/dashboard/install_files/agent.sh"

# output
echo "Setup Complete"
