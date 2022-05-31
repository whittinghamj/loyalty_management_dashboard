#!/bin/bash

MAIN_SERVER_PORT=INSTALL_MAIN_SERVER_PORT

# root sanity check
if [ $(id -u) != "0" ]; then
	echo " "
	echo "Installer needs to be run as 'root' user."
	echo "Try again as root."
	echo " "
	exit 1;
fi

echo "Rebuilding CloudShield Controller:"
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

# download nginx.conf
sudo wget -q -O /etc/nginx/nginx.conf "https://cloudshield.io/dashboard/install_files/controller/build_nginx.php?cluster_id=INSTALL_CLUSTER_ID&server_id=INSTALL_SERVER_ID"

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

# download ssl certs
# sudo wget -q -O /etc/nginx/server.key https://cloudshield.io/dashboard/install_files/server.key.txt
# sudo wget -q -O /etc/nginx/server.crt https://cloudshield.io/dashboard/install_files/server.crt.txt
# sudo wget -q -O /etc/nginx/server.csr https://cloudshield.io/dashboard/install_files/server.csr.txt

# restart nginx
sudo service nginx restart >> $LOG

# nginx sanity check
/usr/sbin/nginx -t
if [ $? -eq 0 ]
then
	# everything looks fine, reload for good measures
    service nginx reload
else
	# looks like something went wrong, try work arounds
	sudo sed -i 's/# load_module/load_module/' /etc/nginx/nginx.conf

	service nginx restart
fi

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
